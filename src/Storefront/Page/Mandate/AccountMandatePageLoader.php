<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Page\Mandate;

use PayonePayment\Components\MandateService\MandateServiceInterface;
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

    /** @var MandateServiceInterface */
    private $mandateService;

    public function __construct(
        GenericPageLoader $genericLoader,
        EventDispatcherInterface $eventDispatcher,
        MandateServiceInterface $MandateService
    ) {
        $this->genericLoader   = $genericLoader;
        $this->eventDispatcher = $eventDispatcher;
        $this->MandateService     = $MandateService;
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
            $this->mandateService->getMandates(
                $context->getCustomer(),
                $context->getContext()
            )
        );

        $event = new AccountMandatePageLoadedEvent($page, $context, $request);
        $this->eventDispatcher->dispatch($event, AccountMandatePageLoadedEvent::NAME);

        return $page;
    }
}
