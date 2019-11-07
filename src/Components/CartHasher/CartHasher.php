<?php

declare(strict_types=1);

namespace PayonePayment\Components\CartHasher;

use LogicException;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CartHasher implements CartHasherInterface
{
    private const VALID_TYPES = [
        Cart::class,
        OrderEntity::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function generate(Struct $entity, SalesChannelContext $context): string
    {
        if (!in_array(get_class($entity), self::VALID_TYPES, true)) {
            throw new LogicException('unsupported struct type during hash creation or validation');
        }

        $hashData = $this->getHashData($entity, $context);

        return $this->generateHash($hashData);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(Struct $entity, string $cartHash, SalesChannelContext $context): bool
    {
        if (!in_array(get_class($entity), self::VALID_TYPES, true)) {
            throw new LogicException('unsupported struct type during hash creation or validation');
        }

        $hashData = $this->getHashData($entity, $context);
        $expected = $this->generateHash($hashData);

        return hash_equals($expected, $cartHash);
    }

    /**
     * @param Cart|OrderEntity $entity
     */
    private function getHashData(Struct $entity, SalesChannelContext $context): array
    {
        $hashData = [];

        if (!method_exists($entity, 'getLineItems')) {
            return $hashData;
        }

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
            throw new LogicException('could not generate hash');
        }

        $secret = getenv('APP_SECRET');

        if (empty($secret)) {
            throw new LogicException('empty app secret');
        }

        return hash_hmac('sha256', $json, $secret);
    }
}
