<?php

declare(strict_types=1);

namespace PayonePayment\Components\CartHasher;

use LogicException;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CartHasher implements CartHasherInterface
{
    public function generateHashFromCart(Cart $cart, SalesChannelContext $context): string
    {
        $details = $this->getDetails($cart);

        return $this->generateHash($details, $context);
    }

    public function validate(OrderEntity $order, SalesChannelContext $context, string $cartHash): bool
    {
        $details  = $this->getDetails($order);
        $expected = $this->generateHash($details, $context);

        return hash_equals($expected, $cartHash);
    }

    private function getDetails(Struct $entity): array
    {
        $details = [];

        if (!method_exists($entity, 'getLineItems')) {
            return $details;
        }

        /** @var LineItem|OrderLineItemEntity $item */
        foreach ($entity->getLineItems() as $item) {
            $detail = [
                'id'       => $item->getId(),
                'type'     => $item->getType(),
                'quantity' => $item->getQuantity(),
            ];

            if (null !== $item->getPrice()) {
                $detail['price'] = $item->getPrice()->getTotalPrice();
            }

            $details[$item->getId()] = $detail;
        }

        return $details;
    }

    private function generateHash(array $details, SalesChannelContext $context): string
    {
        $data = json_encode([$details, $context], JSON_PRESERVE_ZERO_FRACTION);

        if (empty($data)) {
            throw new LogicException('could not generatae hash');
        }

        $secret = getenv('APP_SECRET');

        if (empty($secret)) {
            throw new LogicException('empty app secret');
        }

        return hash_hmac('sha256', $data, $secret);
    }
}
