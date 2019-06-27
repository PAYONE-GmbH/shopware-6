<?php

declare(strict_types=1);

namespace PayonePayment\Components\CardService;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;

interface CardServiceInterface
{
    public function saveCard(
        CustomerEntity $customer,
        string $truncatedCardPan,
        string $pseudoCardPan,
        Context $context
    ): void;

    public function removeCard(
        CustomerEntity $customer,
        string $truncatedCardPan,
        Context $context
    ): void;

    public function getCards(
        CustomerEntity $customer,
        Context $context
    ): EntitySearchResult;
}
