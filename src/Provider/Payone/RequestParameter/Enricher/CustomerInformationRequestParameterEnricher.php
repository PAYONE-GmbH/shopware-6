<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\RequestParameter\Enricher;

use PayonePayment\PaymentHandler\Enum\PayoneBusinessRelationEnum;
use PayonePayment\Payone\Request\RequestConstantsEnum;
use PayonePayment\Payone\RequestParameter\Builder\RequestBuilderServiceAccessor;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class CustomerInformationRequestParameterEnricher implements RequestParameterEnricherInterface
{
    public function __construct(
        protected RequestBuilderServiceAccessor $serviceAccessor,
    ) {
    }

    #[\Override]
    public function enrich(AbstractRequestDto $arguments): array
    {
        $parameters         = [];
        $paymentTransaction = $arguments->paymentTransaction;
        $dataBag            = $arguments->requestData;
        $order              = $paymentTransaction->order;
        $customer           = $order->getOrderCustomer();
        $billingAddress     = $this->getBillingAddress($order, $arguments->salesChannelContext->getContext());

        if (null !== $customer) {
            $parameters['email'] = $customer->getEmail();
        }

        $company                        = $billingAddress->getCompany();
        $parameters['businessrelation'] = PayoneBusinessRelationEnum::BUSINESSRELATION_B2C->value;

        if (!empty($company)) {
            $parameters['company']          = $company;
            $parameters['businessrelation'] = PayoneBusinessRelationEnum::BUSINESSRELATION_B2B->value;

            return $parameters;
        }

        if (!empty($dataBag->get(RequestConstantsEnum::BIRTHDAY->value))) {
            $birthday = \DateTime::createFromFormat('Y-m-d', $dataBag->get(RequestConstantsEnum::BIRTHDAY->value));

            if (!empty($birthday)) {
                $parameters['birthday'] = $birthday->format('Ymd');
            }
        }

        return $parameters;
    }

    protected function getBillingAddress(OrderEntity $order, Context $context): OrderAddressEntity
    {
        $criteria = new Criteria([$order->getBillingAddressId()]);

        /** @var OrderAddressEntity|null $address */
        $address = $this->serviceAccessor->orderAddressRepository->search($criteria, $context)->first();

        if (null === $address) {
            throw new \RuntimeException('missing order customer billing address');
        }

        return $address;
    }
}
