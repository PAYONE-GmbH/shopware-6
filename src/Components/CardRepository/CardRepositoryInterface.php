<?php

declare(strict_types=1);

namespace PayonePayment\Components\CardRepository;

use DateTime;
use PayonePayment\DataAbstractionLayer\Entity\Card\PayonePaymentCardEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;

interface CardRepositoryInterface
{
    public function saveCard(
        CustomerEntity $transaction,
        string $cardholder,
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

    public function getExistingCard(
        CustomerEntity $customer,
        string $pseudoCardPan,
        Context $context
    ): ?PayonePaymentCardEntity;
}
