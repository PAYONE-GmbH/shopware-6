<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Installer\AttributeInstaller;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Paypal\PaypalAuthorizeRequest;
use PayonePayment\Payone\Request\RequestFactory;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class PayonePaypalPaymentHandler implements AsynchronousPaymentHandlerInterface
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

    public function pay(AsyncPaymentTransactionStruct $transaction, Context $context): RedirectResponse
    {
        $request  = $this->requestFactory->generateRequest($transaction, $context, PaypalAuthorizeRequest::class);
        $response = $this->client->request($request);

        if (empty($response['Status']) && $response['Status'] !== 'REDIRECT') {
            throw new \RuntimeException('ExternalPaymentProcessException'); // TODO: replace with correct exception (maybe: ExternalPaymentProcessException)
        }

        $this->orderTransactionRepo->update([
            [
                'id' => $transaction->getOrderTransaction()->getId(),
                'attributes' => [
                    AttributeInstaller::TRANSACTION_ID => $response['TXID'],
                ],
            ]
        ], $context);

        return new RedirectResponse($response['RedirectUrl']);
    }

    public function finalize(string $transactionId, Request $request, Context $context): void
    {
        $state = $request->query->get('state');

        if (empty($state)) {
            throw new \RuntimeException('ExternalPaymentProcessException'); // TODO: replace with correct exception (maybe: ExternalPaymentProcessException)
        }

        if ($state === 'cancel') {
            throw new \RuntimeException('ExternalPaymentProcessException'); // TODO: replace with correct exception (maybe: ExternalPaymentProcessException)
        }

        if ($state === 'error') {
            throw new \RuntimeException('ExternalPaymentProcessException'); // TODO: replace with correct exception (maybe: ExternalPaymentProcessException)
        }

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
