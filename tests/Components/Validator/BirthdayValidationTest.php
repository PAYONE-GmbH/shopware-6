<?php

declare(strict_types=1);

namespace PayonePayment\Components\Validator;

use DateTime;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;

class BirthdayValidationTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @dataProvider successTestCases
     */
    public function testIfValidationIsSuccessful(DateTime $date): void
    {
        /** @var DataValidator $validator */
        $validator = $this->getContainer()->get(DataValidator::class);

        $validator->validate(
            [
                'birthday' => $date->format('Y-m-d'),
            ],
            (new DataValidationDefinition())->add('birthday', new Birthday())
        );

        static::assertTrue(true);
    }

    public static function successTestCases(): array
    {
        return [
            [(DateTime::createFromFormat('Y-m-d', '1950-01-01'))],
            [(new DateTime())->modify('-18 years')],
            [(new DateTime())->modify('-50 years')],
        ];
    }

    /**
     * @dataProvider failTestCases
     */
    public function testIfValidationFails(mixed $date = null): void
    {
        /** @var DataValidator $validator */
        $validator = $this->getContainer()->get(DataValidator::class);

        $this->expectException(ConstraintViolationException::class);

        $validator->validate(
            [
                'birthday' => $date instanceof DateTime ? $date->format('Y-m-d') : $date,
            ],
            (new DataValidationDefinition())->add('birthday', new Birthday())
        );
    }

    public static function failTestCases(): array
    {
        return [
            [null],
            ['1234567'],
            ['abcdefg'],
            [(new DateTime())],
            [(new DateTime())->modify('+1 year')],
            [(new DateTime())->modify('-17 years')],
        ];
    }
}
