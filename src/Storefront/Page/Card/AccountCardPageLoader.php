<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Page\Card;

use PayonePayment\StoreApi\Route\AbstractCardRoute;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\GenericPageLoader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class AccountCardPageLoader
{
    private readonly GenericPageLoader $genericLoader;

    public function __construct(
        GenericPageLoader $genericLoader,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AbstractCardRoute $cardRoute
    ) {
        $this->genericLoader = $genericLoader;
    }

    public function load(Request $request, SalesChannelContext $context): AccountCardPage
    {
        if (!$context->getCustomer()) {
            throw CartException::customerNotLoggedIn();
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
