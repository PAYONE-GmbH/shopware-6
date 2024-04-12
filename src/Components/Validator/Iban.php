<?php

declare(strict_types=1);

namespace PayonePayment\Components\Validator;

use Symfony\Component\Validator\Constraints\IbanValidator;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class Iban extends \Symfony\Component\Validator\Constraints\Iban
{
    protected const ERROR_NAMES = [
        self::INVALID_COUNTRY_CODE_ERROR => 'PAYONE_INVALID_COUNTRY_CODE_ERROR',
        self::INVALID_CHARACTERS_ERROR => 'PAYONE_INVALID_CHARACTERS_ERROR',
        self::CHECKSUM_FAILED_ERROR => 'PAYONE_CHECKSUM_FAILED_ERROR',
        self::INVALID_FORMAT_ERROR => 'PAYONE_INVALID_FORMAT_ERROR',
        self::NOT_SUPPORTED_COUNTRY_CODE_ERROR => 'PAYONE_NOT_SUPPORTED_COUNTRY_CODE_ERROR',
    ];

    /**
     * @phpstan-ignore-next-line
     */
    protected static $errorNames = self::ERROR_NAMES;

    public function validatedBy(): string
    {
        return IbanValidator::class;
    }
}
