<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Page\Card;

use PayonePayment\Components\CardService\CardServiceInterface;
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

    /** @var CardServiceInterface */
    private $cardService;

    public function __construct(
        GenericPageLoader $genericLoader,
        EventDispatcherInterface $eventDispatcher,
        CardServiceInterface $cardService
    ) {
        $this->genericLoader   = $genericLoader;
        $this->eventDispatcher = $eventDispatcher;
        $this->cardService     = $cardService;
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
            $this->cardService->getCards(
                $context->getCustomer(),
                $context->getContext()
            )
        );

        $event = new AccountCardPageLoadedEvent($page, $context, $request);
        $this->eventDispatcher->dispatch($event, AccountCardPageLoadedEvent::NAME);

        return $page;
    }
}
