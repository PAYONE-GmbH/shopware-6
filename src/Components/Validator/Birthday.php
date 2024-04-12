<?php

declare(strict_types=1);

namespace PayonePayment\Components\Validator;

use Symfony\Component\Validator\Constraints\LessThanOrEqual;

class Birthday extends LessThanOrEqual
{
    /**
     * @var int
     */
    final public const LEGAL_AGE = 18;

    /**
     * @var string
     */
    final public const INVALID_AGE = 'PAYONE_BIRTHDAY_NOT_VALID';

    /**
     * @var array<string, string>
     */
    protected const ERROR_NAMES = [
        self::TOO_HIGH_ERROR => self::INVALID_AGE,
    ];

    /**
     * @var string[]
     * @deprecated since Symfony 6.1, use const ERROR_NAMES instead
     */
    protected static $errorNames = self::ERROR_NAMES;

    public function __construct(mixed $options = null)
    {
        $options ??= [];
        $options['value'] = sprintf('-%d years', self::LEGAL_AGE);
        parent::__construct($options);
    }

    public function validatedBy(): string
    {
        return BirthdayValidator::class;
    }
}
