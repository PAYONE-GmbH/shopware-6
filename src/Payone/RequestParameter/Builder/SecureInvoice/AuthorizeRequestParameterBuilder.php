<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\SecureInvoice;

use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydratorInterface;
use PayonePayment\PaymentHandler\PayoneSecureInvoicePaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

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
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $paymentTransaction  = $arguments->getPaymentTransaction();
        $salesChannelContext = $arguments->getSalesChannelContext();
        $order               = $paymentTransaction->getOrder();
        $currency            = $this->getOrderCurrency($order, $salesChannelContext->getContext());

        $parameters = [
            'clearingtype'    => self::CLEARING_TYPE_INVOICE,
            'clearingsubtype' => 'POV',
            'request'         => self::REQUEST_ACTION_AUTHORIZE,
        ];

        if ($order->getLineItems() !== null) {
            $parameters = array_merge($parameters, $this->lineItemHydrator->mapOrderLines($currency, $order->getLineItems()));
        }

        return $parameters;
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayoneSecureInvoicePaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }
}
