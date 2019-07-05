<?php

declare(strict_types=1);

namespace PayonePayment\Components\CardService;

use PayonePayment\DataAbstractionLayer\Entity\Card\SocialEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class CardService implements CardServiceInterface
{
    /** @var EntityRepositoryInterface */
    private $cardRepository;

    public function __construct(EntityRepositoryInterface $cardRepository)
    {
        $this->cardRepository = $cardRepository;
    }

    public function saveCard(
        CustomerEntity $customer,
        string $truncatedCardPan,
        string $pseudoCardPan,
        Context $context
    ): void {
        $card = $this->getExistingCard(
            $customer,
            $truncatedCardPan,
            $context
        );

        $data = [
            'id'               => null === $card ? Uuid::randomHex() : $card->getId(),
            'pseudoCardPan'    => $pseudoCardPan,
            'truncatedCardPan' => $truncatedCardPan,
            'customerId'       => $customer->getId(),
        ];

        $this->cardRepository->upsert([$data], $context);
    }

    public function removeCard(
        CustomerEntity $customer,
        string $truncatedCardPan,
        Context $context
    ): void {
        $card = $this->getExistingCard(
            $customer,
            $truncatedCardPan,
            $context
        );

        if (null === $card) {
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

        return $this->cardRepository->search($criteria, $context);
    }

    protected function getExistingCard(
        CustomerEntity $customer,
        string $truncatedCardPan,
        Context $context
    ): ?SocialEntity {
        $criteria = new Criteria();

        $criteria->addFilter(
            new EqualsFilter('payone_payment_card.truncatedCardPan', $truncatedCardPan)
        );

        $criteria->addFilter(
            new EqualsFilter('payone_payment_card.customerId', $customer->getId())
        );

        return $this->cardRepository->search($criteria, $context)->first();
    }
}
