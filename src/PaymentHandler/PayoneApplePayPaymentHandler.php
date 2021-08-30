<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class PayoneApplePayPaymentHandler extends AbstractPayonePaymentHandler implements SynchronousPaymentHandlerInterface
{
    /** @var PayoneClientInterface */
    protected $client;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var RequestParameterFactory */
    private $requestParameterFactory;

    public function __construct(
        ConfigReaderInterface $configReader,
        PayoneClientInterface $client,
        TranslatorInterface $translator,
        EntityRepositoryInterface $lineItemRepository,
        RequestStack $requestStack,
        RequestParameterFactory $requestParameterFactory
    ) {
        parent::__construct($configReader, $lineItemRepository, $requestStack);

        $this->client                  = $client;
        $this->translator              = $translator;
        $this->requestParameterFactory = $requestParameterFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function pay(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): void
    {
        //TODO: store data on transaction

        //APPROVED / REDIRECT / ERROR / PENDING
        //TODO: Exception on Redirect, Error
    }

    /**
     * {@inheritdoc}
     */
    public static function isCapturable(array $transactionData, array $customFields): bool
    {
        if (static::isNeverCapturable($transactionData, $customFields)) {
            return false;
        }

        $txAction          = isset($transactionData['txaction']) ? strtolower($transactionData['txaction']) : null;
        $transactionStatus = isset($transactionData['transaction_status']) ? strtolower($transactionData['transaction_status']) : null;

        if ($txAction === TransactionStatusService::ACTION_APPOINTED && $transactionStatus === TransactionStatusService::STATUS_COMPLETED) {
            return true;
        }

        return static::matchesIsCapturableDefaults($transactionData, $customFields);
    }

    /**
     * {@inheritdoc}
     */
    public static function isRefundable(array $transactionData, array $customFields): bool
    {
        if (static::isNeverRefundable($transactionData, $customFields)) {
            return false;
        }

        return static::matchesIsRefundableDefaults($transactionData, $customFields);
    }
}
