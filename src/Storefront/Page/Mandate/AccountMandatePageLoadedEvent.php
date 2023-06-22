<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Page\Mandate;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\NestedEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

class AccountMandatePageLoadedEvent extends NestedEvent
{
    final public const NAME = 'account-payone-mandate.page.loaded';

    protected SalesChannelContext $context;

    protected Request $request;

    public function __construct(
        protected AccountMandatePage $page,
        SalesChannelContext $context,
        Request $request
    ) {
        $this->context = $context;
        $this->request = $request;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getContext(): Context
    {
        return $this->context->getContext();
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->context;
    }

    public function getPage(): AccountMandatePage
    {
        return $this->page;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
