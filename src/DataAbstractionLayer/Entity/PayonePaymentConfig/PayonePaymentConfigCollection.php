<?php

declare(strict_types=1);

namespace PayonePayment\DataAbstractionLayer\Entity\PayonePaymentConfig;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class PayonePaymentConfigCollection extends EntityCollection
{
    public function get($key)
    {
        $value = getenv($this->getEnvName($key));

        if (false !== $value) {
            return $value;
        }

        /** @var null|PayonePaymentConfigEntity $entity */
        $entity = parent::get($key);

        if (null === $entity) {
            return null;
        }

        return $entity->getValue();
    }

    protected function getExpectedClass(): string
    {
        return PayonePaymentConfigEntity::class;
    }

    private function getEnvName(?string $key)
    {
        return 'PAYONE_' . strtoupper($key);
    }
}
