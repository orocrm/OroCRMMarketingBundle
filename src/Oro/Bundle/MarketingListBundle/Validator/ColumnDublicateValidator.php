<?php

namespace Oro\Bundle\MarketingListBundle\Validator;

use Oro\Bundle\MarketingListBundle\Validator\Constraints\ColumnDublicateConstraint;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ColumnDublicateValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof Segment) {
            return;
        }

        $definition = json_decode($value->getDefinition(), true);

        /** @var ColumnDublicateConstraint $constraint */
        if (isset($definition['columns']) && $columnNames = $this->checkOnCloumnDublicate($definition['columns'])) {
            $this->context->addViolation($constraint->columnIsDublicate, ['%columnName%' => $columnNames]);
        }
    }

    /**
     * @param array $columns
     * @return bool
     */
    private function checkOnCloumnDublicate(array $columns)
    {
        $useMap = [];
        $result = [];
        foreach ($columns as $key => $value) {
            $key = $value['name'];
            if (isset($useMap[$key])) {
                $result[] = $value['label'];
            }
            $useMap[$key] = $value['name'];
        }
        if ($result) {
            return implode(', ', $result);
        }

        return false;
    }
}
