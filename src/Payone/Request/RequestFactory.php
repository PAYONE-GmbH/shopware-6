<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request;

use LogicException;
use Psr\Container\ContainerInterface;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;

final class RequestFactory
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * TODO: Separate sync and async requests or us a own parameter for the transaction. A real type would be perfect.
     *
     * @param AsyncPaymentTransactionStruct|SyncPaymentTransactionStruct $transaction
     * @param Context                                                    $context
     * @param string                                                     $request
     *
     * @return array
     */
    public function generateRequest($transaction, Context $context, string $request)
    {
        if (!$this->container->has($request)) {
            throw new LogicException('missing service definition for request class: ' . $request);
        }

        /** @var RequestInterface $request */
        $request    = $this->container->get($request);
        $parameters = $request->getRequestParameters($transaction, $context);

        if (!empty($request->getParentRequest())) {
            $parameters = array_merge(
                $this->generateRequest($transaction, $context, $request->getParentRequest()),
                $parameters
            );
        }

        return array_filter($parameters);
    }
}
