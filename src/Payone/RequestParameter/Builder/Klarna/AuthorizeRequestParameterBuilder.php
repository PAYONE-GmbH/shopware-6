<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Klarna;

use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydratorInterface;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class AuthorizeRequestParameterBuilder extends AbstractKlarnaParameterBuilder
{
    public function __construct(private readonly LineItemHydratorInterface $lineItemHydrator)
    {
    }

    /**
     * @param PaymentTransactionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $dataBag = $arguments->getRequestData();

        $parameter = [
            'request' => $arguments->getAction(),
            'clearingtype' => self::CLEARING_TYPE_FINANCING,
            'add_paydata[authorization_token]' => $dataBag->get('payoneKlarnaAuthorizationToken'),
        ];

        $context = $arguments->getSalesChannelContext()->getContext();
        $order = $arguments->getPaymentTransaction()->getOrder();
        $currency = $this->getOrderCurrency($order, $context);
        $lineItems = $this->lineItemHydrator->mapOrderLines($currency, $order, $context);

        return array_merge($parameter, $lineItems);
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return parent::supports($arguments) && $arguments instanceof PaymentTransactionStruct;
    }
}
