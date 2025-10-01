<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\ResponseHandler;

use PayonePayment\DataAbstractionLayer\Entity\Card\PayonePaymentCardEntity;
use PayonePayment\DataHandler\TransactionDataHandler;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\CreatePaymentExceptionTrait;
use PayonePayment\Provider\Payone\Enum\CreditCardRequestParamEnum;
use PayonePayment\ResponseHandler\PrepareOrderTransactionDataTrait;
use PayonePayment\ResponseHandler\ResponseHandlerInterface;
use PayonePayment\Service\CardRepositoryService;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreditCardResponseHandler implements ResponseHandlerInterface
{
    use CreatePaymentExceptionTrait;
    use PrepareOrderTransactionDataTrait;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly TransactionDataHandler $transactionDataHandler,
        private readonly CardRepositoryService $cardRepository,
        private readonly EntityRepository $lineItemRepository,
    ) {
    }

    public function handle(
        string $orderTransactionId,
        PaymentTransaction $paymentTransaction,
        RequestDataBag $dataBag,
        array $request,
        array $response,
        SalesChannelContext $salesChannelContext,
    ): void {
        if (empty($response['status']) || 'ERROR' === $response['status']) {
            throw $this->createPaymentException(
                $orderTransactionId,
                $this->translator->trans('PayonePayment.errorMessages.genericError'),
            );
        }

        $customer           = $salesChannelContext->getCustomer();
        $savedPseudoCardPan = $dataBag->get(CreditCardRequestParamEnum::SAVED_PSEUDO_CARD_PAN->value);

        // TODO-card-holder-requirement: move the next line into the if-block if the $savedPseudoCardPan is empty (please see credit-card handler)
        $cardHolder = $dataBag->get(CreditCardRequestParamEnum::CARD_HOLDER->value);

        if (empty($savedPseudoCardPan)) {
            $truncatedCardPan = $dataBag->get(CreditCardRequestParamEnum::TRUNCATED_CARD_PAN->value);
            $cardExpireDate   = $dataBag->get(CreditCardRequestParamEnum::CARD_EXPIRE_DATE->value);
            $pseudoCardPan    = $dataBag->get(CreditCardRequestParamEnum::PSEUDO_CARD_PAN->value);
            $cardType         = $dataBag->get(CreditCardRequestParamEnum::CARD_TYPE->value);
            $saveCreditCard   = 'on' === $dataBag->get(CreditCardRequestParamEnum::SAVE_CREDIT_CARD->value);
            $expiresAt        = \DateTime::createFromFormat('ym', $cardExpireDate);

            if (!empty($expiresAt) && null !== $customer && $saveCreditCard) {
                $this->cardRepository->saveCard(
                    $customer,
                    $cardHolder,
                    $truncatedCardPan,
                    $pseudoCardPan,
                    $cardType,
                    $expiresAt,
                    $salesChannelContext->getContext(),
                );
            }
        } else {
            $cardType = '';

            if ($customer) {
                $savedCard = $this->cardRepository->getExistingCard(
                    $customer,
                    $savedPseudoCardPan,
                    $salesChannelContext->getContext(),
                );

                // TODO-card-holder-requirement: remove this if-statement incl. content (please see credit-card handler)
                if ($savedCard instanceof PayonePaymentCardEntity && empty($savedCard->getCardHolder())) {
                    $this->cardRepository->saveMissingCardHolder(
                        $savedCard->getId(),
                        $customer->getId(),
                        $cardHolder,
                        $salesChannelContext->getContext(),
                    );
                }

                $cardType = $savedCard ? $savedCard->getCardType() : '';
            }
        }

        $data = $this->prepareOrderTransactionData($request, $response, [
            'additionalData' => [
                'card_type' => $cardType,
            ],
        ]);

        $this->transactionDataHandler->saveTransactionData(
            $paymentTransaction,
            $salesChannelContext->getContext(),
            $data,
        );

        if (null !== $paymentTransaction->getOrder()->getLineItems()) {
            $this->setLineItemCustomFields(
                $paymentTransaction->getOrder()->getLineItems(),
                $salesChannelContext->getContext(),
            );
        }
    }

    private function setLineItemCustomFields(
        OrderLineItemCollection $lineItem,
        Context $context,
        array $fields = [],
    ): void {
        $customFields = \array_merge([
            CustomFieldInstaller::CAPTURED_QUANTITY => 0,
            CustomFieldInstaller::REFUNDED_QUANTITY => 0,
        ], $fields);

        $saveData = [];

        foreach ($lineItem->getElements() as $lineItemEntity) {
            $saveData[] = [
                'id'           => $lineItemEntity->getId(),
                'customFields' => \array_merge($lineItemEntity->getCustomFields() ?? [], $customFields),
            ];
        }

        $this->lineItemRepository->update($saveData, $context);
    }
}
