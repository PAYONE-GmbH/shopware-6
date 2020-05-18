<?php

declare(strict_types=1);

namespace PayonePayment\Components\RequestHandler;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\DataHandler\LineItem\LineItemDataHandlerInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\RequestHandler\AbstractRequestHandler;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentMethod\PayonePayolutionDebit;
use PayonePayment\PaymentMethod\PayoneSofortBanking;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\PayolutionDebit\PayolutionDebitAuthorizeRequestFactory;
use PayonePayment\Payone\Request\PayolutionDebit\PayolutionDebitPreAuthorizeRequestFactory;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class SofortBankingRequestHandler extends AbstractRequestHandler
{
    public function supports(string $paymentMethodId): bool
    {
        return $paymentMethodId === PayoneSofortBanking::UUID;
    }

    public function getAdditionalRequestParameters(PaymentTransaction $transaction, Context $context, ParameterBag $parameterBag = null): array
    {
        return [];
    }
}
