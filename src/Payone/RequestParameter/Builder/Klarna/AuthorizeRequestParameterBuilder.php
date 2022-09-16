<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\Klarna;

use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydratorInterface;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;

class AuthorizeRequestParameterBuilder extends AbstractKlarnaParameterBuilder
{
    /** @var LineItemHydratorInterface */
    private $lineItemHydrator;

    public function __construct(LineItemHydratorInterface $lineItemHydrator)
    {
        $this->lineItemHydrator = $lineItemHydrator;
    }

    /**
     * @param PaymentTransactionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $dataBag = $arguments->getRequestData();

        $parameter = [
            'request'                          => $arguments->getAction(),
            'clearingtype'                     => self::CLEARING_TYPE_FINANCING,
            'add_paydata[authorization_token]' => $dataBag->get('payoneKlarnaAuthorizationToken'),
        ];

        $order     = $arguments->getPaymentTransaction()->getOrder();
        $lineItems = $this->lineItemHydrator->mapOrderLines($order->getCurrency(), $order, $arguments->getSalesChannelContext()->getContext());

        return array_merge($parameter, $lineItems);
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        return parent::supports($arguments) && $arguments instanceof PaymentTransactionStruct;
    }
}
