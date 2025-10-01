<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Webhook\Handler;

use PayonePayment\Components\TransactionStatus\Enum\TransactionActionEnum;
use PayonePayment\DataHandler\TransactionDataHandler;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;
use Symfony\Component\HttpFoundation\Request;

readonly class PaymentStatusHandler implements WebhookHandlerInterface
{
    public function __construct(
        private TransactionDataHandler $transactionDataHandler,
        private StateMachineRegistry $stateMachineRegistry,
    ) {
    }

    public function process(SalesChannelContext $salesChannelContext, Request $request): void
    {
        $paymentTransaction = $this->transactionDataHandler->getPaymentTransactionByPayoneTransactionId(
            $salesChannelContext->getContext(),
            $request->request->getInt('txid'),
        );

        if (!$paymentTransaction instanceof PaymentTransaction) {
            return;
        }

        $this->stateMachineRegistry->transition(
            new Transition(
                OrderTransactionDefinition::ENTITY_NAME,
                $paymentTransaction->getOrderTransaction()->getId(),
                StateMachineTransitionActions::ACTION_AUTHORIZE,
                'stateId',
            ),
            $salesChannelContext->getContext(),
        );
    }

    public function supports(SalesChannelContext $salesChannelContext, array $data): bool
    {
        return isset($data['txid'])
            && ($data['txaction'] ?? null) === TransactionActionEnum::APPOINTED->value
        ;
    }
}
