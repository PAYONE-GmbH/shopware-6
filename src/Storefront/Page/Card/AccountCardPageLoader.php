<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Page\Card;

use PayonePayment\StoreApi\Route\AbstractCardRoute;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\GenericPageLoader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

readonly class AccountCardPageLoader
{
    public function __construct(
        private GenericPageLoader $genericLoader,
        private EventDispatcherInterface $eventDispatcher,
        private AbstractCardRoute $cardRoute,
    ) {
    }

    public function load(Request $request, SalesChannelContext $context): AccountCardPage
    {
        if (!$context->getCustomer()) {
            throw CartException::customerNotLoggedIn();
        }

        $page = AccountCardPage::createFrom(
            $this->genericLoader->load($request, $context),
        );

        // Disable storage of credit card data
        // $page->setCards(
        //     $this->cardRoute->load($context)->getSearchResult(),
        // );

        $this->eventDispatcher->dispatch(
            new AccountCardPageLoadedEvent($page, $context, $request),
        );

        return $page;
    }
}
