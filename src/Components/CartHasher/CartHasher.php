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
        $hashData = $this->getHashData($cart, $context);

        return $this->generateHash($hashData);
    }

    public function validate(OrderEntity $order, SalesChannelContext $context, string $cartHash): bool
    {
        $hashData = $this->getHashData($order, $context);
        $expected = $this->generateHash($hashData);

        return hash_equals($expected, $cartHash);
    }

    private function getHashData(Struct $entity, SalesChannelContext $context): array
    {
        $hashData = [];

        if (!method_exists($entity, 'getLineItems')) {
            return $hashData;
        }

        /** @var LineItem|OrderLineItemEntity $item */
        foreach ($entity->getLineItems() as $item) {
            $detail = [
                'id'       => $item->getReferencedId(),
                'type'     => $item->getType(),
                'quantity' => $item->getQuantity(),
            ];

            if (null !== $item->getPrice()) {
                $detail['price'] = $item->getPrice()->getTotalPrice();
            }

            $hashData[] = $detail;
        }

        $hashData['currency']       = $context->getCurrency()->getId();
        $hashData['paymentMethod']  = $context->getPaymentMethod()->getId();
        $hashData['shippingMethod'] = $context->getShippingMethod()->getId();

        if (null === $context->getCustomer()) {
            return $hashData;
        }

        $billingAddress = $context->getCustomer()->getActiveBillingAddress();
        if (null !== $billingAddress) {
            $hashData['address'] = [
                'salutation'      => $billingAddress->getSalutationId(),
                'title'           => $billingAddress->getTitle(),
                'firstname'       => $billingAddress->getFirstName(),
                'lastname'        => $billingAddress->getLastName(),
                'street'          => $billingAddress->getStreet(),
                'addressaddition' => $billingAddress->getAdditionalAddressLine1(),
                'zip'             => $billingAddress->getZipcode(),
                'city'            => $billingAddress->getCity(),
                'country'         => $billingAddress->getCountryId(),
            ];
        }

        $hashData['customer'] = [
            'language' => $context->getCustomer()->getLanguageId(),
            'email'    => $context->getCustomer()->getEmail(),
        ];

        if (null !== $context->getCustomer()->getBirthday()) {
            $hashData['birthday'] = $context->getCustomer()->getBirthday()->format(DATE_W3C);
        }

        return $hashData;
    }

    private function generateHash(array $hashData): string
    {
        $json = json_encode($hashData, JSON_PRESERVE_ZERO_FRACTION);

        if (empty($json)) {
            throw new LogicException('could not generatae hash');
        }

        $secret = getenv('APP_SECRET');

        if (empty($secret)) {
            throw new LogicException('empty app secret');
        }

        return hash_hmac('sha256', $json, $secret);
    }
}
