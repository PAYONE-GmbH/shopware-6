<?php

declare(strict_types=1);

namespace PayonePayment\Provider\PayPal\ResponseHandler;

use PayonePayment\DataHandler\TransactionDataHandler;
use PayonePayment\ResponseHandler\EmptyAdditionalTransactionDataTrait;
use PayonePayment\ResponseHandler\HandleAsynchronousResponseTrait;
use PayonePayment\ResponseHandler\ResponseHandlerInterface;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\Translation\TranslatorInterface;

class StandardResponseHandler implements ResponseHandlerInterface
{
    use EmptyAdditionalTransactionDataTrait;
    use HandleAsynchronousResponseTrait;

    public function __construct(
        TranslatorInterface $translator,
        TransactionDataHandler $transactionDataHandler,
    ) {
        $this->translator             = $translator;
        $this->transactionDataHandler = $transactionDataHandler;
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
                $paymentTransaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError'),
            );
        }

        $data = $this->prepareOrderTransactionData($request, $response);

        $this->transactionDataHandler->saveTransactionData(
            $paymentTransaction,
            $salesChannelContext->getContext(),
            $data,
        );
    }

}
