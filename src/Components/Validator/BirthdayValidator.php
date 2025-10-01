<?php

declare(strict_types=1);

namespace PayonePayment\Components\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\LessThanOrEqualValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class BirthdayValidator extends LessThanOrEqualValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Birthday) {
            throw new UnexpectedTypeException($constraint, Birthday::class);
        }

        $value = ($value ? \DateTime::createFromFormat('Y-m-d', $value) : null) ?: null;

        if (!$value instanceof \DateTime) {
            // if value is null the comparison `null < DateTime` will be result into true.
            $value = new \DateTime(); // `now` is not allowed by constraint
        }

        parent::validate($value, $constraint);
    }
}
