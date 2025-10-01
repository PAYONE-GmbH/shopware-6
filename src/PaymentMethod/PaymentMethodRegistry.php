<?php

declare(strict_types=1);

namespace PayonePayment\PaymentMethod;

use Shopware\Core\Framework\Struct\Collection;

/**
 * @extends Collection<PaymentMethodInterface>
 */
class PaymentMethodRegistry extends Collection
{
    public function hasId(string $id): bool
    {
        /** @var PaymentMethodInterface $element */
        foreach ($this->elements as $element) {
            if ($element::getId() === $id) {
                return true;
            }
        }

        return false;
    }

    public function getById(string $id): PaymentMethodInterface|null
    {
        /** @var PaymentMethodInterface $element */
        foreach ($this->elements as $element) {
            if ($element::getId() === $id) {
                return $element;
            }
        }

        return null;
    }

    public function getByHandler(string $paymentHandlerClassName): PaymentMethodInterface|null
    {
        /** @var PaymentMethodInterface $element */
        foreach ($this->elements as $element) {
            if ($element->getPaymentHandlerClassName() === $paymentHandlerClassName) {
                return $element;
            }
        }

        return null;
    }
}
