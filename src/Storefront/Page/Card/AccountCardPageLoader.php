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
    private GenericPageLoader $genericLoader;

    private EventDispatcherInterface $eventDispatcher;

    private AbstractCardRoute $cardRoute;

    public function __construct(
        GenericPageLoader $genericLoader,
        EventDispatcherInterface $eventDispatcher,
        AbstractCardRoute $cardRoute
    ) {
        $this->genericLoader = $genericLoader;
        $this->eventDispatcher = $eventDispatcher;
        $this->cardRoute = $cardRoute;
    }

    public function load(Request $request, SalesChannelContext $context): AccountCardPage
    {
        if (!$context->getCustomer()) {
            // ToDo 6.5: Die CartException gibt es erst ab 6.4.15.0. Ok das dann als neue Mindestversion zu nehmen? (Kommt auch an anderen Stellen vor)
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
