<?php

declare(strict_types=1);

namespace PayonePayment\Components\CartHasher;

use Exception;
use LogicException;
use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector;

class CartHasher implements CartHasherInterface
{
    private const VALID_TYPES = [
        Cart::class,
        OrderEntity::class,
    ];

    /** @var CurrencyPrecisionInterface */
    private $currencyPrecision;

    public function __construct(CurrencyPrecisionInterface $currencyPrecision)
    {
        $this->currencyPrecision = $currencyPrecision;
    }

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

        if (null !== $entity->getLineItems()) {
            foreach ($entity->getLineItems() as $lineItem) {
                try {
                    if (class_exists('Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector') &&
                        CustomizedProductsCartDataCollector::CUSTOMIZED_PRODUCTS_TEMPLATE_LINE_ITEM_TYPE === $lineItem->getType() &&
                        /** @phpstan-ignore-next-line */
                        null === $lineItem->getParentId()) {
                        continue;
                    }
                } catch (Exception $exception) {
                    // Catch class not found if SwagCustomizedProducts plugin is not installed
                }

                $detail = [
                    'id'       => $lineItem->getReferencedId() ?? '',
                    'type'     => $lineItem->getType(),
                    'quantity' => $lineItem->getQuantity(),
                ];

                if (null !== $lineItem->getPrice()) {
                    $detail['price'] = $this->currencyPrecision->getRoundedItemAmount($lineItem->getPrice()->getTotalPrice(), $context->getCurrency());
                }

                $hashData[] = $detail;
            }
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
