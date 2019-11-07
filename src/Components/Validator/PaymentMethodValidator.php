<?php

declare(strict_types=1);

namespace PayonePayment\Components\Validator;

use Symfony\Component\Validator\Constraints\AbstractComparisonValidator;

class PaymentMethodValidator extends AbstractComparisonValidator
{
    /**
     * @return bool always fails as the check is done inside a subscriber
     */
    protected function compareValues($value1, $value2)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getErrorCode()
    {
        return PaymentMethod::INVALID_ERROR;
    }
}
