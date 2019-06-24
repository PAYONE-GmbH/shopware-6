<?php

declare(strict_types=1);

namespace PayonePayment\Components\CardHandler;

use PayonePayment\DataAbstractionLayer\Entity\Card\PayonePaymentCardEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class CardHandler implements CardHandlerInterface
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
        $card = $this->getExistingCard($customer->getId(), $truncatedCardPan, $context);

        $data = [
            'id'               => null === $card ? Uuid::randomHex() : $card->getId(),
            'pseudoCardPan'    => $pseudoCardPan,
            'truncatedCardPan' => $truncatedCardPan,
            'customerId'       => $customer->getId(),
        ];

        $this->cardRepository->upsert([$data], $context);
    }

    protected function getExistingCard(
        string $customer,
        string $truncatedCardPan,
        Context $context
    ): ?PayonePaymentCardEntity {
        $criteria = new Criteria();

        $criteria->addFilter(
            new EqualsFilter('payone_payment_card.truncatedCardPan', $truncatedCardPan)
        );

        $criteria->addFilter(
            new EqualsFilter('payone_payment_card.customerId', $customer)
        );

        return $this->cardRepository->search($criteria, $context)->first();
    }
}
