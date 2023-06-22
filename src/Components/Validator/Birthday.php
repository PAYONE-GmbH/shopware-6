<?php

declare(strict_types=1);

namespace PayonePayment\Components\Validator;

use Symfony\Component\Validator\Constraints\AbstractComparison;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class Birthday extends AbstractComparison
{
    final public const TOO_HIGH_ERROR = 'ac2f93c6-f906-47c9-8b09-6f7cf41f4f49';

    protected const ERROR_NAMES = [
        self::TOO_HIGH_ERROR => 'PAYONE_BIRTHDAY_NOT_VALID',
    ];

    /**
     * @phpstan-ignore-next-line
     */
    public $message = 'This value should be less than or equal to {{ compared_value }}.';
}
