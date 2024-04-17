<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\CartHasher\CartHasherInterface;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\DataHandler\OrderActionLog\OrderActionLogDataHandlerInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\Validator\Birthday;
use PayonePayment\Components\Validator\Iban;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class PayonePayolutionInstallmentPaymentHandler extends AbstractSynchronousPayonePaymentHandler
{
    public function __construct(
        ConfigReaderInterface $configReader,
        EntityRepository $lineItemRepository,
        RequestStack $requestStack,
        PayoneClientInterface $client,
        TranslatorInterface $translator,
        TransactionDataHandlerInterface $transactionDataHandler,
        OrderActionLogDataHandlerInterface $orderActionLogDataHandler,
        RequestParameterFactory $requestParameterFactory,
        protected CartHasherInterface $cartHasher
    ) {
        parent::__construct(
            $configReader,
            $lineItemRepository,
            $requestStack,
            $client,
            $translator,
            $transactionDataHandler,
            $orderActionLogDataHandler,
            $requestParameterFactory
        );
    }

    public function pay(
        SyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): void {
        $this->cartHasher->validateRequest($dataBag, $transaction, $salesChannelContext);

        parent::pay($transaction, $dataBag, $salesChannelContext);
    }

    public function getValidationDefinitions(SalesChannelContext $salesChannelContext): array
    {
        $definitions = parent::getValidationDefinitions($salesChannelContext);

        $definitions['payolutionConsent'] = [new NotBlank()];
        $definitions['payoneBirthday'] = [new NotBlank(), new Birthday()];

        $definitions['payolutionInstallmentDuration'] = [new NotBlank()];
        $definitions['payolutionAccountOwner'] = [new NotBlank()];
        $definitions['payolutionIban'] = [new NotBlank(), new Iban()];
        $definitions['payolutionBic'] = [new NotBlank()];

        return $definitions;
    }

    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if (static::isNeverCapturable($payoneTransActionData)) {
            return false;
        }

        return static::isTransactionAppointedAndCompleted($transactionData) || static::matchesIsCapturableDefaults($transactionData);
    }

    public static function isRefundable(array $transactionData): bool
    {
        if (static::isNeverRefundable($transactionData)) {
            return false;
        }

        return static::matchesIsRefundableDefaults($transactionData);
    }

    protected function getDefaultAuthorizationMethod(): string
    {
        return AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE;
    }

    protected function getAdditionalTransactionData(RequestDataBag $dataBag, array $request, array $response): array
    {
        return [
            'clearingReference' => $response['clearing']['Reference'],
            'captureMode' => AbstractPayonePaymentHandler::PAYONE_STATE_COMPLETED,
            'clearingType' => AbstractPayonePaymentHandler::PAYONE_CLEARING_FNC,
            'financingType' => AbstractPayonePaymentHandler::PAYONE_FINANCING_PYS,
        ];
    }
}
