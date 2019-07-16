<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Page\Mandate;

use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Storefront\Page\Page;

class AccountMandatePage extends Page
{
    /** @var EntitySearchResult */
    protected $mandates;

    public function getMandates(): EntitySearchResult
    {
        return $this->mandates;
    }

    public function setMandates(EntitySearchResult $mandates): void
    {
        $this->mandates = $mandates;
    }
}
