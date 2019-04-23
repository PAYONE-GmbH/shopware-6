<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request;

use LogicException;
use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use Psr\Container\ContainerInterface;
use Shopware\Core\Framework\Context;

final class RequestFactory
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function generateRequest(PaymentTransactionStruct $transaction, Context $context, string $request)
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
