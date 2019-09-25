<?php

declare(strict_types=1);

namespace PayonePayment\Components\CardRepository;

use DateTime;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;

interface CardRepositoryInterface
{
    public function saveCard(
        CustomerEntity $transaction,
        string $truncatedCardPan,
        string $pseudoCardPan,
        DateTime $expiresAt,
        Context $context
    ): void;

    public function removeCard(
        CustomerEntity $customer,
        string $pseudoCardPan,
        Context $context
    ): void;

    public function getCards(
        CustomerEntity $customer,
        Context $context
    ): EntitySearchResult;
}
