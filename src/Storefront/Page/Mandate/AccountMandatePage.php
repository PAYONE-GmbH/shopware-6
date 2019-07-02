<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Page\Mandate;

use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Storefront\Page\Page;

class AccountMandatePage extends Page
{
    /** @var EntitySearchResult */
    protected $Mandates;

    public function getMandates(): EntitySearchResult
    {
        return $this->Mandates;
    }

    public function setMandates(EntitySearchResult $Mandates): void
    {
        $this->Mandates = $Mandates;
    }
}
