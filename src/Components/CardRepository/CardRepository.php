<?php

declare(strict_types=1);

namespace PayonePayment\Components\CardRepository;

use PayonePayment\DataAbstractionLayer\Entity\Card\PayonePaymentCardEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;

class CardRepository implements CardRepositoryInterface
{
    public function __construct(private readonly EntityRepository $cardRepository)
    {
    }

    public function saveCard(
        CustomerEntity $customer,
        string $truncatedCardPan,
        string $pseudoCardPan,
        string $cardType,
        \DateTime $expiresAt,
        Context $context
    ): void {
        $card = $this->getExistingCard(
            $customer,
            $pseudoCardPan,
            $context
        );

        $expiresAt->setTime(23, 59, 59);
        $expiresAt->modify('last day of this month');

        $data = [
            'id' => $card === null ? Uuid::randomHex() : $card->getId(),
            'pseudoCardPan' => $pseudoCardPan,
            'truncatedCardPan' => $truncatedCardPan,
            'cardType' => $cardType,
            'expiresAt' => $expiresAt,
            'customerId' => $customer->getId(),
        ];

        $this->cardRepository->upsert([$data], $context);
    }

    public function removeCard(
        CustomerEntity $customer,
        string $pseudoCardPan,
        Context $context
    ): void {
        $card = $this->getExistingCard(
            $customer,
            $pseudoCardPan,
            $context
        );

        if ($card === null) {
            return;
        }

        $this->cardRepository->delete([['id' => $card->getId()]], $context);
    }

    public function getCards(
        CustomerEntity $customer,
        Context $context
    ): EntitySearchResult {
        $criteria = new Criteria();

        $criteria->addFilter(
            new EqualsFilter('payone_payment_card.customerId', $customer->getId())
        );

        if (empty($criteria->getSorting())) {
            $criteria->addSorting(
                new FieldSorting('expiresAt', FieldSorting::DESCENDING)
            );
        }

        return $this->cardRepository->search($criteria, $context);
    }

    public function removeAllCardsForCustomer(CustomerEntity $customer, Context $context): void
    {
        $cards = $this->getCards($customer, $context);

        $ids = array_map(static fn ($item) => ['id' => $item], array_values($cards->getIds()));

        $this->cardRepository->delete($ids, $context);
    }

    public function getExistingCard(
        CustomerEntity $customer,
        string $pseudoCardPan,
        Context $context
    ): ?PayonePaymentCardEntity {
        $criteria = new Criteria();

        $criteria->addFilter(
            new EqualsFilter('payone_payment_card.pseudoCardPan', $pseudoCardPan),
            new EqualsFilter('payone_payment_card.customerId', $customer->getId())
        );

        return $this->cardRepository->search($criteria, $context)->first();
    }
}
