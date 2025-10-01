<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter\Enricher;

use PayonePayment\Payone\Request\RequestConstantsEnum;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\ParameterBag;

trait ApplyBirthdayParameterTrait
{
    protected function applyBirthdayParameter(
        OrderEntity $order,
        array &$parameters,
        ParameterBag $dataBag,
        Context $context,
        bool $isOptional = false,
    ): void {
        if (\is_string($birthday = $dataBag->get(RequestConstantsEnum::BIRTHDAY->value))) {
            $birthday = \DateTime::createFromFormat('Y-m-d', $birthday) ?: null;
        }

        if ($birthday instanceof \DateTimeInterface) {
            $parameters['birthday'] = $birthday->format('Ymd');

            return;
        }

        $birthday = $order->getOrderCustomer()?->getCustomer()?->getBirthday();

        if ($birthday instanceof \DateTimeInterface) {
            $parameters['birthday'] = $birthday->format('Ymd');

            return;
        }

        if (!$isOptional) {
            throw new \RuntimeException('missing birthday');
        }
    }
}
