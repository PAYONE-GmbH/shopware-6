<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Paypal\PaypalAuthorizeRequest;
use PayonePayment\Payone\Request\RequestFactory;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\StateMachine\StateMachineRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class PayonePaypalPaymentHandler implements PaymentHandlerInterface
{
    /** @var RequestFactory */
    private $requestFactory;

    /** @var PayoneClientInterface */
    private $client;

    /** @var EntityRepositoryInterface */
    private $transactionRepository;

    /** @var StateMachineRegistry */
    private $stateMachineRegistry;

    public function __construct(
        RequestFactory $requestFactory,
        PayoneClientInterface $client,
        EntityRepositoryInterface $transactionRepository,
        StateMachineRegistry $stateMachineRegistry
    ) {
        $this->requestFactory        = $requestFactory;
        $this->client                = $client;
        $this->transactionRepository = $transactionRepository;
        $this->stateMachineRegistry  = $stateMachineRegistry;
    }

    public function pay(PaymentTransactionStruct $transaction, Context $context): ?RedirectResponse
    {
        $request  = $this->requestFactory->generateRequest($transaction, $context, PaypalAuthorizeRequest::class);
        $response = $this->client->request($request);

        if (!empty($response['Status']) && $response['Status'] === 'REDIRECT') {
            return new RedirectResponse($response['RedirectUrl']);
        }

        return new RedirectResponse($transaction->getReturnUrl());
    }

    public function finalize(string $transactionId, Request $request, Context $context): void
    {
        $stateId = $this->stateMachineRegistry->getStateByTechnicalName(
            Defaults::ORDER_TRANSACTION_STATE_MACHINE,
            Defaults::ORDER_TRANSACTION_STATES_PAID, $context
        )->getId();

        $transaction = [
            'id'      => $transactionId,
            'stateId' => $stateId,
        ];

        $this->transactionRepository->update([$transaction], $context);
    }
}
