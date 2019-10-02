<?php

declare(strict_types=1);

namespace PayonePayment\Components\Validator;

use DateTime;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\AbstractComparisonValidator;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class BirthdayValidator extends AbstractComparisonValidator
{
    /**
     * Compares the two given values to find if their relationship is valid.
     *
     * @param mixed $value1 The first value to compare
     * @param mixed $value2 The second value to compare
     *
     * @return bool true if the relationship is valid, false otherwise
     */
    protected function compareValues($value1, $value2)
    {
        $birthday = DateTime::createFromFormat('Y-m-d', $value1);

        return $value2->diff($birthday)->days <= 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function getErrorCode()
    {
        return Birthday::TOO_HIGH_ERROR;
    }
}
