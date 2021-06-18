<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\SecureInvoice;

use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydratorInterface;
use PayonePayment\PaymentHandler\PayoneSecureInvoicePaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Struct\Struct;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @var LineItemHydratorInterface */
    protected $lineItemHydrator;

    /** @var EntityRepositoryInterface */
    protected $currencyRepository;

    public function __construct(LineItemHydratorInterface $lineItemHydrator, EntityRepositoryInterface $currencyRepository)
    {
        $this->lineItemHydrator   = $lineItemHydrator;
        $this->currencyRepository = $currencyRepository;
    }

    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(
        Struct $arguments
    ): array {
        $paymentTransaction  = $arguments->getPaymentTransaction();
        $salesChannelContext = $arguments->getSalesChannelContext();
        $order               = $paymentTransaction->getOrder();
        $currency            = $this->getOrderCurrency($order, $salesChannelContext->getContext());

        $parameters = [
            'clearingtype'    => 'rec',
            'clearingsubtype' => 'POV',
            'request'         => 'authorization',
        ];

        //TODO: might be an additional builder
        if ($order->getLineItems() !== null) {
            $parameters = array_merge($parameters, $this->lineItemHydrator->mapOrderLines($currency, $order->getLineItems()));
        }
        //TODO: lineItemHydrator->mappedByRequest - really needed as in original factory?

        return $parameters;
    }

    /** @param PaymentTransactionStruct $arguments */
    public function supports(Struct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayoneSecureInvoicePaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }
}
