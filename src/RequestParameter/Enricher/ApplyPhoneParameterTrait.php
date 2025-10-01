<?php

declare(strict_types=1);

namespace PayonePayment\RequestParameter\Enricher;

use PayonePayment\Payone\Request\RequestConstantsEnum;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\HttpFoundation\ParameterBag;

trait ApplyPhoneParameterTrait
{
    protected readonly EntityRepository $orderAddressRepository;

    protected function applyPhoneParameter(
        OrderEntity $order,
        array &$parameters,
        ParameterBag $dataBag,
        Context $context,
        bool $isOptional = false,
    ): void {
        $phoneNumber = $dataBag->get(RequestConstantsEnum::PHONE->value);

        if (empty($phoneNumber)) {
            $orderAddress = $order->getBillingAddress();

            if (null === $orderAddress) {
                /** @var OrderAddressEntity|null $orderAddress */
                $orderAddress = $this->orderAddressRepository->search(
                    new Criteria([ $order->getBillingAddressId() ]),
                    $context,
                )->first();
            }

            $phoneNumber = $orderAddress?->getPhoneNumber();
        }

        if (empty($phoneNumber)) {
            if (!$isOptional) {
                throw new \RuntimeException('missing phone number');
            }

            return;
        }

        $parameters['telephonenumber'] = $phoneNumber;
    }
}
