<?php

declare(strict_types=1);

namespace PayonePayment\ResponseHandler;

use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

trait HandleAsynchronousResponseTrait
{
    use HandleSynchronousResponseTrait;

    public function handle(
        string $orderTransactionId,
        PaymentTransaction $paymentTransaction,
        RequestDataBag $dataBag,
        array $request,
        array $response,
        SalesChannelContext $salesChannelContext,
    ): void {
        if (empty($response['status']) || 'REDIRECT' !== $response['status']) {
            throw $this->createPaymentException(
                $orderTransactionId,
                $this->translator->trans('PayonePayment.errorMessages.genericError'),
            );
        }

        $data = $this->prepareOrderTransactionData(
            $request,
            $response,
            $this->getAdditionalTransactionData($dataBag, $request, $response),
        );

        $this->transactionDataHandler->saveTransactionData(
            $paymentTransaction,
            $salesChannelContext->getContext(),
            $data,
        );
    }
}
