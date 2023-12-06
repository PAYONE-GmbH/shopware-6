<?php

declare(strict_types=1);

namespace PayonePayment\Components\CartHasher;

use PayonePayment\Components\Currency\CurrencyPrecisionInterface;
use PayonePayment\Components\Exception\InvalidCartHashException;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector;

class CartHasher implements CartHasherInterface
{
    public function __construct(
        private readonly CurrencyPrecisionInterface $currencyPrecision,
        private readonly string $appSecret = ''
    ) {
    }

    public function generate(Cart|OrderEntity $entity, SalesChannelContext $context): string
    {
        $hashData = $this->getHashData($entity, $context);

        return $this->generateHash($hashData);
    }

    public function validate(Cart|OrderEntity $entity, string $cartHash, SalesChannelContext $context): bool
    {
        $hashData = $this->getHashData($entity, $context);
        $expected = $this->generateHash($hashData);

        return hash_equals($expected, $cartHash);
    }

    public function validateRequest(
        RequestDataBag $requestDataBag,
        AsyncPaymentTransactionStruct|SyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext,
        ?string $exceptionClass = null
    ): void {
        $cartHash = (string) $requestDataBag->get('carthash');

        if (!$this->validate($paymentTransaction->getOrder(), $cartHash, $salesChannelContext)) {
            throw new InvalidCartHashException();
        }
    }

    public function getCriteriaForOrder(?string $orderId = null): Criteria
    {
        $criteria = (new Criteria())
            ->addAssociation('lineItems')
            ->addAssociation('currency')
            ->addAssociation('paymentMethod')
            ->addAssociation('shippingMethod')
            ->addAssociation('customer');

        if ($orderId) {
            $criteria->setIds([$orderId]);
        }

        return $criteria;
    }

    private function getHashData(Cart|OrderEntity $entity, SalesChannelContext $context): array
    {
        $hashData = [];

        $hashData['items'] = [];
        if ($entity->getLineItems() !== null) {
            foreach ($entity->getLineItems() as $lineItem) {
                try {
                    if (class_exists('Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector')
                        && $lineItem->getType() === CustomizedProductsCartDataCollector::CUSTOMIZED_PRODUCTS_TEMPLATE_LINE_ITEM_TYPE
                        && (($lineItem instanceof LineItem) || $lineItem->getParentId() === null)) {
                        continue;
                    }
                } catch (\Exception) {
                    // Catch class not found if SwagCustomizedProducts plugin is not installed
                }

                $detail = [
                    'id' => $lineItem->getReferencedId() ?? '',
                    'type' => $lineItem->getType(),
                    'quantity' => $lineItem->getQuantity(),
                ];

                if ($lineItem->getPrice() !== null) {
                    $detail['price'] = $this->currencyPrecision->getRoundedItemAmount($lineItem->getPrice()->getTotalPrice(), $context->getCurrency());
                }

                $hashData['items'][] = md5((string) json_encode($detail, \JSON_THROW_ON_ERROR));
            }
        }

        // sort hashed items to make sure, they are always in the same order (cart/order) cause Shopware will re-order
        // the items after placing the order for an unknown reason. If we collect the items and pass them directly into
        // the hash, the items may be in a different order, after the order has been placed --> validation will fail.
        sort($hashData['items']);

        $hashData['currency'] = $context->getCurrency()->getId();
        $hashData['paymentMethod'] = $context->getPaymentMethod()->getId();
        $hashData['shippingMethod'] = $context->getShippingMethod()->getId();

        if ($context->getCustomer() === null) {
            return $hashData;
        }

        $billingAddress = $context->getCustomer()->getActiveBillingAddress();

        if ($billingAddress !== null) {
            $hashData['address'] = [
                'salutation' => $billingAddress->getSalutationId(),
                'title' => $billingAddress->getTitle(),
                'firstname' => $billingAddress->getFirstName(),
                'lastname' => $billingAddress->getLastName(),
                'street' => $billingAddress->getStreet(),
                'addressaddition' => $billingAddress->getAdditionalAddressLine1(),
                'zip' => $billingAddress->getZipcode(),
                'city' => $billingAddress->getCity(),
                'country' => $billingAddress->getCountryId(),
            ];
        }

        $hashData['customer'] = [
            'language' => $context->getCustomer()->getLanguageId(),
            'email' => $context->getCustomer()->getEmail(),
        ];

        if ($context->getCustomer()->getBirthday() !== null) {
            $hashData['birthday'] = $context->getCustomer()->getBirthday()->format(\DATE_W3C);
        }

        return $hashData;
    }

    private function generateHash(array $hashData): string
    {
        $json = json_encode($hashData, \JSON_PRESERVE_ZERO_FRACTION | \JSON_THROW_ON_ERROR);

        if (empty($json)) {
            throw new \LogicException('could not generate hash');
        }

        if (empty($this->appSecret)) {
            throw new \LogicException('empty app secret');
        }

        return hash_hmac('sha256', $json, $this->appSecret);
    }
}
