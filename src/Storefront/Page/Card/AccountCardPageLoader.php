<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Page\Card;

use PayonePayment\StoreApi\Route\AbstractCardRoute;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\GenericPageLoader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class AccountCardPageLoader
{
    /** @var GenericPageLoader */
    private $genericLoader;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var AbstractCardRoute */
    private $cardRoute;

    public function __construct(
        GenericPageLoader $genericLoader,
        EventDispatcherInterface $eventDispatcher,
        AbstractCardRoute $cardRoute
    ) {
        $this->genericLoader   = $genericLoader;
        $this->eventDispatcher = $eventDispatcher;
        $this->cardRoute       = $cardRoute;
    }

    public function load(Request $request, SalesChannelContext $context): AccountCardPage
    {
        if (!$context->getCustomer()) {
            throw new CustomerNotLoggedInException();
        }

        $page = AccountCardPage::createFrom(
            $this->genericLoader->load($request, $context)
        );

        $page->setCards(
            $this->cardRoute->load($context)->getSearchResult()
        );

        $this->eventDispatcher->dispatch(
            new AccountCardPageLoadedEvent($page, $context, $request)
        );

        return $page;
    }
}
