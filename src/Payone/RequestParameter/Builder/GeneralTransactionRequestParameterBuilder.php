<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\Components\CartHasher\CartHasherInterface;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class GeneralTransactionRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @var CartHasherInterface */
    private $cartHasher;

    public function __construct(CartHasherInterface $cartHasher)
    {
        $this->cartHasher = $cartHasher;
    }

    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(
        Struct $arguments
    ): array {
        $paymentTransaction  = $arguments->getPaymentTransaction();
        $salesChannelContext = $arguments->getSalesChannelContext();
        $requestData         = $arguments->getRequestData();
        $paymentMethod       = $arguments->getPaymentMethod();
        $currency            = $this->getOrderCurrency($paymentTransaction->getOrder(), $salesChannelContext->getContext());

        $parameters = [
            'amount'      => $this->getConvertedAmount($paymentTransaction->getOrder()->getAmountTotal(), $currency->getDecimalPrecision()),
            'currency'    => $currency->getIsoCode(),
            'reference'   => $this->getReferenceNumber($paymentTransaction, true),
            'workorderid' => $this->getWorkOrderId($paymentTransaction, $requestData, $salesChannelContext),
        ];

        $this->addNarrativeTextIfAllowed(
            $parameters,
            $salesChannelContext->getSalesChannel()->getId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIXES[$paymentMethod],
            (string) $paymentTransaction->getOrder()->getOrderNumber()
        );

        return $parameters;
    }

    /** @param PaymentTransactionStruct $arguments */
    public function supports(Struct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        return true;
    }

    private function getWorkOrderId(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $context
    ): ?string {
        $workOrderId = $dataBag->get('workorder');

        if (null === $workOrderId) {
            return null;
        }

        $cartHash = $dataBag->get('carthash');

        if (null === $cartHash) {
            return null;
        }

        if (!$this->cartHasher->validate($transaction->getOrder(), $cartHash, $context)) {
            return null;
        }

        return $workOrderId;
    }
}
