<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\TrackingBundle\Migration\Extension\IdentifierEventExtension;
use Oro\Bundle\TrackingBundle\Tools\IdentifierVisitConfigDumperExtension;

class IdentifierVisitConfigDumperExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var IdentifierVisitConfigDumperExtension */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $identifyProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $associationBuilder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->identifyProvider = $this
            ->createMock('Oro\Bundle\TrackingBundle\Provider\TrackingEventIdentificationProvider');

        $this->associationBuilder = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Tools\AssociationBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new IdentifierVisitConfigDumperExtension(
            $this->identifyProvider,
            $this->configManager,
            $this->associationBuilder
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset(
            $this->extension,
            $this->configManager,
            $this->identifyProvider,
            $this->associationBuilder
        );
    }

    /**
     * @dataProvider supportsProvider
     *
     * @param string $className
     * @param null   $configs
     * @param bool   $expected
     */
    public function testSupports($className, $configs, $expected)
    {
        $this->prepareMocks($className, $configs);

        $this->assertEquals(
            $expected,
            $this->extension->supports(ExtendConfigDumper::ACTION_PRE_UPDATE)
        );

        $this->assertFalse(
            $this->extension->supports(ExtendConfigDumper::ACTION_POST_UPDATE)
        );
    }

    public function supportsProvider()
    {
        return [
            [
                'Test\Entity1',
                [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'state' => ExtendScope::STATE_NEW,
                    'upgradeable' => false
                ],
                false
            ],
            [
                'Test\Entity1',
                [],
                false
            ],
            [
                'Test\Entity1',
                [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'state' => ExtendScope::STATE_NEW,
                    'upgradeable' => true
                ],
                true
            ]
        ];
    }

    /**
     * @depends testSupports
     */
    public function testPreUpdate()
    {
        $data = $this->supportsProvider();
        $data = end($data);

        $this->prepareMocks($data[0], $data[1]);

        $this->associationBuilder
            ->expects($this->once())
            ->method('createManyToOneAssociation')
            ->with(
                'Oro\Bundle\TrackingBundle\Entity\TrackingVisit',
                $data[0],
                IdentifierEventExtension::ASSOCIATION_KIND
            );

        $this->extension->preUpdate();
    }

    /**
     * @param string $className
     * @param null   $configs
     */
    protected function prepareMocks($className, $configs)
    {
        $this->identifyProvider
            ->expects($this->once())
            ->method('getTargetIdentityEntities')
            ->will($this->returnValue([$className]));

        $extendConfig = new Config(new EntityConfigId('extend', $className));
        if (!empty($configs)) {
            foreach ($configs as $name => $value) {
                $extendConfig->set($name, $value);
            }
        }

        $extendProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $extendProvider
            ->expects($this->once())
            ->method('getConfigs')
            ->will($this->returnValue([$extendConfig]));
        $extendProvider
            ->expects($this->any())
            ->method('hasConfig')
            ->with('Oro\Bundle\TrackingBundle\Entity\TrackingVisit')
            ->will($this->returnValue(true));

        $this->configManager
            ->expects($this->any())
            ->method('getProvider')
            ->with('extend')
            ->will($this->returnValue($extendProvider));
    }
}
