<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Page\Card;

use PayonePayment\Components\CardRepository\CardRepositoryInterface;
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

    /** @var CardRepositoryInterface */
    private $cardRepository;

    public function __construct(
        GenericPageLoader $genericLoader,
        EventDispatcherInterface $eventDispatcher,
        CardRepositoryInterface $cardRepository
    ) {
        $this->genericLoader   = $genericLoader;
        $this->eventDispatcher = $eventDispatcher;
        $this->cardRepository  = $cardRepository;
    }

    public function load(Request $request, SalesChannelContext $context): AccountCardPage
    {
        if (!$context->getCustomer()) {
            throw new CustomerNotLoggedInException();
        }

        $page = AccountCardPage::createFrom(
            $this->genericLoader->load($request, $context)
        );

        if (null !== $context->getCustomer()) {
            $page->setCards(
                $this->cardRepository->getCards(
                    $context->getCustomer(),
                    $context->getContext()
                )
            );
        }

        $this->eventDispatcher->dispatch(
            new AccountCardPageLoadedEvent($page, $context, $request)
        );

        return $page;
    }
}
