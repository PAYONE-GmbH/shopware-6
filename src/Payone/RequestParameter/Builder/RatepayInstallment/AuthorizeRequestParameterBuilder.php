<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\RatepayInstallment;

use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\Components\DeviceFingerprint\AbstractDeviceFingerprintService;
use PayonePayment\Components\Helper\OrderFetcherInterface;
use PayonePayment\Components\Hydrator\LineItemHydrator\LineItemHydratorInterface;
use PayonePayment\Components\Ratepay\Profile\ProfileServiceInterface;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\RatepayDebit\AuthorizeRequestParameterBuilder as RatepayDebitAuthorizeRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class AuthorizeRequestParameterBuilder extends RatepayDebitAuthorizeRequestParameterBuilder
{
    protected CurrencyPrecisionInterface $currencyPrecision;

    public function __construct(
        OrderFetcherInterface $orderFetcher,
        ProfileServiceInterface $profileService,
        AbstractDeviceFingerprintService $deviceFingerprintService,
        EntityRepositoryInterface $customerRepository,
        LineItemHydratorInterface $lineItemHydrator,
        CurrencyPrecisionInterface $currencyPrecision
    ) {
        parent::__construct(
            $orderFetcher,
            $profileService,
            $deviceFingerprintService,
            $customerRepository,
            $lineItemHydrator
        );
        $this->currencyPrecision = $currencyPrecision;
    }

    /**
     * @param PaymentTransactionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $dataBag = $arguments->getRequestData();
        $salesChannelContext = $arguments->getSalesChannelContext();
        $context = $salesChannelContext->getContext();
        $paymentTransaction = $arguments->getPaymentTransaction();
        $order = $this->getOrder($paymentTransaction->getOrder()->getId(), $context);
        $currency = $this->getOrderCurrency($order, $context);
        $profile = $this->getProfile($order, PayoneRatepayInstallmentPaymentHandler::class);

        $parameters = [
            'request' => self::REQUEST_ACTION_AUTHORIZE,
            'clearingtype' => self::CLEARING_TYPE_FINANCING,
            'financingtype' => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPS,
            'add_paydata[customer_allow_credit_inquiry]' => 'yes',
            'add_paydata[installment_amount]' => $this->currencyPrecision->getRoundedTotalAmount((float) $dataBag->get('ratepayInstallmentAmount'), $currency),
            'add_paydata[installment_number]' => (int) $dataBag->get('ratepayInstallmentNumber'),
            'add_paydata[last_installment_amount]' => $this->currencyPrecision->getRoundedTotalAmount((float) $dataBag->get('ratepayLastInstallmentAmount'), $currency),
            'add_paydata[interest_rate]' => $this->currencyPrecision->getRoundedTotalAmount((float) $dataBag->get('ratepayInterestRate'), $currency),
            'add_paydata[amount]' => $this->currencyPrecision->getRoundedTotalAmount((float) $dataBag->get('ratepayTotalAmount'), $currency),
            'add_paydata[shop_id]' => $profile->getShopId(),
            'add_paydata[device_token]' => $this->deviceFingerprintService->getDeviceIdentToken($salesChannelContext),
        ];

        if ($dataBag->get('ratepayIban')) {
            $parameters['iban'] = $dataBag->get('ratepayIban');
            $parameters['add_paydata[debit_paytype]'] = 'DIRECT-DEBIT';
        } else {
            $parameters['add_paydata[debit_paytype]'] = 'BANK-TRANSFER';
        }

        $this->applyPhoneParameter($order, $parameters, $dataBag->get('ratepayPhone') ?? '', $context);
        $this->applyBirthdayParameterWithoutCustomField($parameters, $dataBag);

        if ($order->getLineItems() !== null) {
            $parameters = array_merge($parameters, $this->lineItemHydrator->mapOrderLines($currency, $order, $context));
        }

        return $parameters;
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action = $arguments->getAction();

        return $paymentMethod === PayoneRatepayInstallmentPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }
}
