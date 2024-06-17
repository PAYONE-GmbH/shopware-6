<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\CardRepository\CardRepositoryInterface;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\CustomerDataPersistor\CustomerDataPersistor;
use PayonePayment\Components\DataHandler\OrderActionLog\OrderActionLogDataHandlerInterface;
use PayonePayment\Components\DataHandler\Transaction\TransactionDataHandlerInterface;
use PayonePayment\Components\PaymentStateHandler\PaymentStateHandlerInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\DataAbstractionLayer\Entity\Card\PayonePaymentCardEntity;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class PayoneCreditCardPaymentHandler extends AbstractAsynchronousPayonePaymentHandler
{
    final public const REQUEST_PARAM_SAVE_CREDIT_CARD = 'saveCreditCard';
    final public const REQUEST_PARAM_PSEUDO_CARD_PAN = 'pseudoCardPan';
    final public const REQUEST_PARAM_SAVED_PSEUDO_CARD_PAN = 'savedPseudoCardPan';
    final public const REQUEST_PARAM_CARD_EXPIRE_DATE = 'cardExpireDate';
    final public const REQUEST_PARAM_CARD_TYPE = 'cardType';
    final public const REQUEST_PARAM_TRUNCATED_CARD_PAN = 'truncatedCardPan';
    final public const REQUEST_PARAM_CARD_HOLDER = 'cardHolder';

    public function __construct(
        ConfigReaderInterface $configReader,
        EntityRepository $lineItemRepository,
        RequestStack $requestStack,
        PayoneClientInterface $client,
        TranslatorInterface $translator,
        TransactionDataHandlerInterface $transactionDataHandler,
        OrderActionLogDataHandlerInterface $orderActionLogDataHandler,
        PaymentStateHandlerInterface $stateHandler,
        RequestParameterFactory $requestParameterFactory,
        CustomerDataPersistor $customerDataPersistor,
        protected CardRepositoryInterface $cardRepository
    ) {
        parent::__construct(
            $configReader,
            $lineItemRepository,
            $requestStack,
            $client,
            $translator,
            $transactionDataHandler,
            $orderActionLogDataHandler,
            $stateHandler,
            $requestParameterFactory,
            $customerDataPersistor
        );
    }

    public function getValidationDefinitions(DataBag $dataBag, SalesChannelContext $salesChannelContext): array
    {
        $definitions = parent::getValidationDefinitions($dataBag, $salesChannelContext);

        // Please note: this is field is only required, for that case, that the card has been already saved, but no
        // card-holder has been saved (because this field was added in a later version)
        // with that we want to make sure, that a card-holder is always present.
        // if a card holder as been already saved, the submitted value will be ignored.
        // if no card holder has been saved, and no values has been submitted, the next line will cause a validation-error
        // TODO in the far future: move this into the if-block for the case, if the card has not been saved.
        // search for the following to-do reference, to adjust the related code: TODO-card-holder-requirement
        $definitions[self::REQUEST_PARAM_CARD_HOLDER] = [new NotBlank()];

        if (empty($dataBag->get(self::REQUEST_PARAM_SAVED_PSEUDO_CARD_PAN))) {
            $definitions[self::REQUEST_PARAM_PSEUDO_CARD_PAN] = [new NotBlank()];
            $definitions[self::REQUEST_PARAM_TRUNCATED_CARD_PAN] = [new NotBlank()];
            $definitions[self::REQUEST_PARAM_CARD_EXPIRE_DATE] = [new NotBlank()];
            $definitions[self::REQUEST_PARAM_CARD_TYPE] = [new NotBlank()];
        }

        return $definitions;
    }

    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if (static::isNeverCapturable($payoneTransActionData)) {
            return false;
        }

        $txAction = isset($transactionData['txaction']) ? strtolower((string)$transactionData['txaction']) : null;

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
            throw $this->createPaymentException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }

        $customer = $salesChannelContext->getCustomer();
        $savedPseudoCardPan = $dataBag->get(self::REQUEST_PARAM_SAVED_PSEUDO_CARD_PAN);

        // TODO-card-holder-requirement: move the next line into the if-block if the $savedPseudoCardPan is empty (please see credit-card handler)
        $cardHolder = $dataBag->get(self::REQUEST_PARAM_CARD_HOLDER);

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
                    $cardHolder,
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

                // TODO-card-holder-requirement: remove this if-statement incl. content (please see credit-card handler)
                if ($savedCard instanceof PayonePaymentCardEntity && empty($savedCard->getCardHolder())) {
                    $this->cardRepository->saveMissingCardHolder($savedCard->getId(), $customer->getId(), $cardHolder, $salesChannelContext->getContext());
                }

                $cardType = $savedCard ? $savedCard->getCardType() : '';
            }
        }

        $data = $this->preparePayoneOrderTransactionData($request, $response, [
            'additionalData' => [
                'card_type' => $cardType,
            ],
        ]);
        $this->transactionDataHandler->saveTransactionData($paymentTransaction, $salesChannelContext->getContext(), $data);

        if ($paymentTransaction->getOrder()->getLineItems() !== null) {
            $this->setLineItemCustomFields($paymentTransaction->getOrder()->getLineItems(), $salesChannelContext->getContext());
        }
    }

    protected function getRedirectResponse(SalesChannelContext $context, array $request, array $response): RedirectResponse
    {
        if (strtolower((string)$response['status']) === 'redirect') {
            return new RedirectResponse($response['redirecturl']);
        }

        return new RedirectResponse($request['successurl']);
    }
}
