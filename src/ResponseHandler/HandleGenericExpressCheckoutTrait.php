<?php

declare(strict_types=1);

namespace PayonePayment\ResponseHandler;

use PayonePayment\DataHandler\TransactionDataHandler;
use PayonePayment\PaymentHandler\CreatePaymentExceptionTrait;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\Translation\TranslatorInterface;

trait HandleGenericExpressCheckoutTrait
{
    use CreatePaymentExceptionTrait;
    use PrepareOrderTransactionDataTrait;

    protected readonly TranslatorInterface $translator;

    protected readonly TransactionDataHandler $transactionDataHandler;

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

        $data = $this->prepareOrderTransactionData($request, $response);

        $this->transactionDataHandler->saveTransactionData(
            $paymentTransaction,
            $salesChannelContext->getContext(),
            $data,
        );
    }
}
