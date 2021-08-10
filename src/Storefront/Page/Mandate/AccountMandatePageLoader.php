<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Page\Mandate;

use PayonePayment\StoreApi\Route\AbstractMandateRoute;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\GenericPageLoader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class AccountMandatePageLoader
{
    /** @var GenericPageLoader */
    private $genericLoader;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var AbstractMandateRoute */
    private $mandateRoute;

    public function __construct(
        GenericPageLoader $genericLoader,
        EventDispatcherInterface $eventDispatcher,
        AbstractMandateRoute $mandateRoute
    ) {
        $this->genericLoader   = $genericLoader;
        $this->eventDispatcher = $eventDispatcher;
        $this->mandateRoute    = $mandateRoute;
    }

    public function load(Request $request, SalesChannelContext $context): AccountMandatePage
    {
        if (!$context->getCustomer()) {
            throw new CustomerNotLoggedInException();
        }

        $page = AccountMandatePage::createFrom(
            $this->genericLoader->load($request, $context)
        );

        $page->setMandates(
            $this->mandateRoute->load(
                $context
            )->getSearchResult()
        );

        $this->eventDispatcher->dispatch(
            new AccountMandatePageLoadedEvent($page, $context, $request)
        );

        return $page;
    }
}
