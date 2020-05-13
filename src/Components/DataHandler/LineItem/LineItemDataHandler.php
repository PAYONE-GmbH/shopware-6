<?php

declare(strict_types=1);

namespace PayonePayment\Components\DataHandler\LineItem;

use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class LineItemDataHandler implements LineItemDataHandlerInterface
{
    /** @var EntityRepositoryInterface */
    private $lineItemRepository;

    public function __construct(EntityRepositoryInterface $lineItemRepository)
    {
        $this->lineItemRepository = $lineItemRepository;
    }

    public function saveLineItemData(OrderLineItemEntity $lineItem, Context $context, array $data): void
    {
        $customFields = $lineItem->getCustomFields() ?? [];
        $customFields = array_merge($customFields, $data);

        $this->updateLineItemCustomFields($lineItem, $context, $customFields);
    }

    public function saveLineItemDataById(string $lineItemId, Context $context, array $data): void
    {
        $searchCriteria =  new Criteria([$lineItemId]);

        $lineItem = $this->lineItemRepository->search($searchCriteria, $context)->first();

        if(!$lineItem instanceof OrderLineItemEntity) {
            return;
        }

        $this->saveLineItemData($lineItem, $context, $data);
    }

    private function updateLineItemCustomFields(OrderLineItemEntity $lineItem, Context $context, array $customFields): void
    {
        $update = [
            'id'           => $lineItem->getId(),
            'customFields' => $customFields,
        ];

        $lineItem->setCustomFields($customFields);

        $this->lineItemRepository->update([$update], $context);
    }
}
