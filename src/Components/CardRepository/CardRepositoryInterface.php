<?php

declare(strict_types=1);

namespace PayonePayment\Components\CardRepository;

use PayonePayment\DataAbstractionLayer\Entity\Card\PayonePaymentCardEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;

interface CardRepositoryInterface
{
    public function saveCard(
        CustomerEntity $customer,
        string $cardHolder,
        string $truncatedCardPan,
        string $pseudoCardPan,
        string $cardType,
        \DateTime $expiresAt,
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

    public function removeAllCardsForCustomer(
        CustomerEntity $customer,
        Context $context
    ): void;

    public function getExistingCard(
        CustomerEntity|string $customer,
        string $pseudoCardPan,
        Context $context
    ): ?PayonePaymentCardEntity;

    /**
     * TODO-card-holder-requirement: remove this method (please see credit-card handler)
     * @deprecated
     */
    public function saveMissingCardHolder(string $cardId, string $customerId, mixed $cardHolder, Context $context): void;
}
