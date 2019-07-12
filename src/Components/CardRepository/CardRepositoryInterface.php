<?php

declare(strict_types=1);

namespace PayonePayment\Components\CardRepository;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;

interface CardRepositoryInterface
{
    public function saveCard(
        CustomerEntity $transaction,
        string $truncatedCardPan,
        string $pseudoCardPan,
        Context $context
    ): void;
}
