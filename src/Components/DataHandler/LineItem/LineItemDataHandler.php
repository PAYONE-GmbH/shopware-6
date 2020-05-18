<?php

declare(strict_types=1);

namespace PayonePayment\Components\DataHandler\LineItem;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class LineItemDataHandler implements LineItemDataHandlerInterface
{
    /** @var EntityRepositoryInterface */
    private $lineItemRepository;

    public function __construct(EntityRepositoryInterface $lineItemRepository)
    {
        $this->lineItemRepository = $lineItemRepository;
    }

    public function saveLineItemData(array $lineItemData, Context $context): void
    {
        $this->lineItemRepository->update([$lineItemData], $context);
    }
}
