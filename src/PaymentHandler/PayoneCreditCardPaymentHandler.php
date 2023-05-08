<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\CardRepository\CardRepositoryInterface;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\PaymentStateHandler\PaymentStateHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class PayoneCreditCardPaymentHandler extends AbstractAsynchronousPayonePaymentHandler
{
    final public const REQUEST_PARAM_SAVE_CREDIT_CARD = 'saveCreditCard';
    final public const REQUEST_PARAM_PSEUDO_CARD_PAN = 'pseudoCardPan';
    final public const REQUEST_PARAM_SAVED_PSEUDO_CARD_PAN = 'savedPseudoCardPan';
    final public const REQUEST_PARAM_CARD_EXPIRE_DATE = 'cardExpireDate';
    final public const REQUEST_PARAM_CARD_TYPE = 'cardType';
    final public const REQUEST_PARAM_TRUNCATED_CARD_PAN = 'truncatedCardPan';

    public function __construct(
        ConfigReaderInterface $configReader,
        EntityRepository $lineItemRepository,
        RequestStack $requestStack,
        PayoneClientInterface $client,
        TranslatorInterface $translator,
        TransactionDataHandlerInterface $dataHandler,
        PaymentStateHandlerInterface $stateHandler,
        RequestParameterFactory $requestParameterFactory,
        protected CardRepositoryInterface $cardRepository
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

    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if (static::isNeverCapturable($payoneTransActionData)) {
            return false;
        }

        $txAction = isset($transactionData['txaction']) ? strtolower((string) $transactionData['txaction']) : null;

        if ($txAction === TransactionStatusService::ACTION_APPOINTED) {
            return true;
        }

        return static::matchesIsCapturableDefaults($transactionData);
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

    protected function handleResponse(
        AsyncPaymentTransactionStruct $transaction,
        PaymentTransaction $paymentTransaction,
        RequestDataBag $dataBag,
        array $request,
        array $response,
        SalesChannelContext $salesChannelContext
    ): void {
        if (empty($response['status']) || $response['status'] === 'ERROR') {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }

        $customer = $salesChannelContext->getCustomer();
        $savedPseudoCardPan = $dataBag->get(self::REQUEST_PARAM_SAVED_PSEUDO_CARD_PAN);

        if (empty($savedPseudoCardPan)) {
            $truncatedCardPan = $dataBag->get(self::REQUEST_PARAM_TRUNCATED_CARD_PAN);
            $cardExpireDate = $dataBag->get(self::REQUEST_PARAM_CARD_EXPIRE_DATE);
            $pseudoCardPan = $dataBag->get(self::REQUEST_PARAM_PSEUDO_CARD_PAN);
            $cardType = $dataBag->get(self::REQUEST_PARAM_CARD_TYPE);
            $saveCreditCard = $dataBag->get(self::REQUEST_PARAM_SAVE_CREDIT_CARD) === 'on';
            $expiresAt = \DateTime::createFromFormat('ym', $cardExpireDate);

            if (!empty($expiresAt) && $customer !== null && $saveCreditCard) {
                $this->cardRepository->saveCard(
                    $customer,
                    $truncatedCardPan,
                    $pseudoCardPan,
                    $cardType,
                    $expiresAt,
                    $salesChannelContext->getContext()
                );
            }
        } else {
            $cardType = '';

            if ($customer) {
                $savedCard = $this->cardRepository->getExistingCard(
                    $customer,
                    $savedPseudoCardPan,
                    $salesChannelContext->getContext()
                );

                $cardType = $savedCard ? $savedCard->getCardType() : '';
            }
        }

        $data = $this->preparePayoneOrderTransactionData($request, $response, [
            'additionalData' => [
                'card_type' => $cardType,
            ],
        ]);
        $this->dataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);

        if ($paymentTransaction->getOrder()->getLineItems() !== null) {
            $this->setLineItemCustomFields($paymentTransaction->getOrder()->getLineItems(), $salesChannelContext->getContext());
        }
    }

    protected function getRedirectResponse(array $request, array $response): RedirectResponse
    {
        if (strtolower($response['status']) === 'redirect') {
            return new RedirectResponse($response['redirecturl']);
        }

        return new RedirectResponse($request['successurl']);
    }
}
