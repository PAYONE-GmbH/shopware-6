<?php

declare(strict_types=1);

namespace PayonePayment\Components\Validator;

use Symfony\Component\Validator\Constraints\AbstractComparison;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class PaymentMethod extends AbstractComparison
{
    public const INVALID_ERROR = '8608fc941e7a4407bc8d259866ca64b4';

    public $message = 'The selected payment method {{ compared_value }} is not available.';

    protected static $errorNames = [
        self::INVALID_ERROR => 'PAYONE_INVALID_PAYMENT_METHOD',
    ];
}
