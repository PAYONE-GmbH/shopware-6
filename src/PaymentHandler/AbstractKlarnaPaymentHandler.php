<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\CartHasher\CartHasherInterface;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\PaymentStateHandler\PaymentStateHandlerInterface;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractKlarnaPaymentHandler extends AbstractAsynchronousPayonePaymentHandler
{
    public function __construct(
        ConfigReaderInterface $configReader,
        EntityRepository $lineItemRepository,
        RequestStack $requestStack,
        PayoneClientInterface $client,
        TranslatorInterface $translator,
        TransactionDataHandlerInterface $dataHandler,
        PaymentStateHandlerInterface $stateHandler,
        RequestParameterFactory $requestParameterFactory,
        protected CartHasherInterface $cartHasher
    ) {
        parent::__construct(
            $configReader,
            $lineItemRepository,
            $requestStack,
            $client,
            $translator,
            $dataHandler,
            $stateHandler,
            $requestParameterFactory
        );
    }

    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        $this->cartHasher->validateRequest($dataBag, $transaction, $salesChannelContext);

        $authToken = $dataBag->get('payoneKlarnaAuthorizationToken');

        if (!$authToken) {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }

        return parent::pay($transaction, $this->filterRequestDataBag($dataBag), $salesChannelContext);
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
        return AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE;
    }

    protected function getAdditionalTransactionData(RequestDataBag $dataBag, array $request, array $response): array
    {
        return [
            'clearingType' => AbstractPayonePaymentHandler::PAYONE_CLEARING_FNC,
        ];
    }

    private function filterRequestDataBag(RequestDataBag $dataBag): RequestDataBag
    {
        $dataBag = clone $dataBag; // prevent modifying the original object
        $allowedParameters = [
            'workorder',
            'payonePaymentMethod',
            'payoneKlarnaAuthorizationToken',
            'carthash',
        ];
        foreach ($dataBag->keys() as $key) {
            if (!\in_array($key, $allowedParameters, true)) {
                $dataBag->remove($key);
            }
        }

        return $dataBag;
    }
}
