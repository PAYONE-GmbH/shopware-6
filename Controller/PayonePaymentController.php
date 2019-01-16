<?php

declare(strict_types=1);

namespace PayonePayment\Controller;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\CheckoutContext;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Storefront\Framework\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * TODO: Investigate the CheckoutContext - with ngrok the context is null
 */
class PayonePaymentController extends StorefrontController
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route("/payone_payment/cancel", name="payone_payment_cancel")
     *
     * @param Request         $request
     * @param CheckoutContext $context
     *
     * @return Response
     */
    public function cancel(Request $request, CheckoutContext $context): Response
    {
        // TODO: redirect to checkout cart overview so the customer can choose a different payment method
        // TODO: this might not be possible at the moment as the cart is empty after the pay method in a paymenthandler is called

        return $this->renderStorefront('@PayonePayment/frontend/payone_payment/cancel.html.twig');
    }

    /**
     * @Route("/payone_payment/error", name="payone_payment_error")
     *
     * @param Request         $request
     * @param CheckoutContext $context
     *
     * @return Response
     */
    public function error(Request $request, CheckoutContext $context): Response
    {
        // TODO: fetch error message and assign to error template

        return $this->renderStorefront('@PayonePayment/frontend/payone_payment/error.html.twig');
    }

    /**
     * @Route("/payone_payment/status", name="payone_payment_status")
     *
     * @param Request         $request
     * @param CheckoutContext $context
     *
     * @return Response
     */
    public function status(Request $request): Response
    {
        // you'll need to include the $defaults array somehow, or at least get the key from a secret configuration file
        if ($request->get('key') !== hash('md5', 'cgXdc7e2J9kJaIm6')) {
            return new Response('TSNOTOK');
        }

        $this->logger->debug('payone_payment - status', [
            'data' => $request->query->all(),
        ]);

        /*
         * TODO: Save status call data to a new table
         * TODO: To improve performance, move all code to the shutdown process of php
         *
        $criteria = new \Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria();
        $criteria->addFilter(new EqualsFilter('order_transaction.id', $transaction->getTransactionId()));


        $orderTransaction = $this->transactionRepository->search($criteria, $context)->get($transaction->getTransactionId());

        $data = [
            'id'                      => $transactionId,
            'details' => Defaults::ORDER_TRANSACTION_COMPLETED,
        ];

        $this->transactionRepository->update([$data], $context);*/

        return new Response('TSOK');
    }
}
