<?php

declare(strict_types=1);

namespace PayonePayment\Components\Validator;

use DateTime;
use DateTimeInterface as DateTimeInterfaceAlias;
use Symfony\Component\Validator\Constraints\AbstractComparisonValidator;

class BirthdayValidator extends AbstractComparisonValidator
{
    /**
     * @param string                 $value1
     * @param DateTimeInterfaceAlias $value2
     *
     * @return bool true if value1 is lower than value2, false otherwise
     */
    protected function compareValues($value1, $value2)
    {
        $birthday = DateTime::createFromFormat('Y-m-d', $value1);

        return $birthday < $value2;
    }

    /**
     * {@inheritdoc}
     */
    protected function getErrorCode()
    {
        return Birthday::TOO_HIGH_ERROR;
    }
}
