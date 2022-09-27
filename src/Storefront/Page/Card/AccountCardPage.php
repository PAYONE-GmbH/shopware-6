<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Page\Card;

use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Storefront\Page\Page;

class AccountCardPage extends Page
{
    protected EntitySearchResult $cards;

    public function getCards(): EntitySearchResult
    {
        return $this->cards;
    }

    public function setCards(EntitySearchResult $cards): void
    {
        $this->cards = $cards;
    }
}
