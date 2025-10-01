<?php

declare(strict_types=1);

namespace PayonePayment\Service;

use PayonePayment\Exception\InvalidCartHashException;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\CustomizedProducts\Core\Checkout\CustomizedProductsCartDataCollector;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

readonly class CartHasherService
{
    private Serializer $serializer;

    public function __construct(
        private CurrencyPrecisionService $currencyPrecision,
        private string $appSecret = '',
    ) {
        $this->serializer = new Serializer(encoders: [ new JsonEncoder() ]);
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

        return \hash_equals($expected, $cartHash);
    }

    public function validateRequest(
        RequestDataBag $requestDataBag,
        PaymentTransactionStruct $paymentTransaction,
        OrderEntity $order,
        SalesChannelContext $salesChannelContext,
        string|null $exceptionClass = null,
    ): void {
        $cartHash = (string) $requestDataBag->get('carthash');

        if (!$this->validate($order, $cartHash, $salesChannelContext)) {
            throw new InvalidCartHashException();
        }
    }

    public function getCriteriaForOrder(string|null $orderId = null): Criteria
    {
        $criteria = (new Criteria())
            ->addAssociation('currency')
            ->addAssociation('customer')
            ->addAssociation('lineItems')
            ->addAssociation('paymentMethod')
            ->addAssociation('shippingMethod')
        ;

        if ($orderId) {
            $criteria->setIds([ $orderId ]);
        }

        return $criteria;
    }

    private function getHashData(Cart|OrderEntity $entity, SalesChannelContext $context): array
    {
        $hashData          = [];
        $hashData['items'] = [];

        foreach ($entity->getLineItems() ?? [] as $lineItem) {
            $lineItemType = $lineItem->getType();

            if (
                \class_exists(CustomizedProductsCartDataCollector::class)
                && CustomizedProductsCartDataCollector::CUSTOMIZED_PRODUCTS_TEMPLATE_LINE_ITEM_TYPE === $lineItemType
                && (
                    ($lineItem instanceof LineItem) || null === $lineItem->getParentId()
                )
            ) {
                continue;
            }

            $detail = [
                'id'       => $lineItem->getReferencedId() ?? '',
                'type'     => $lineItem->getType(),
                'quantity' => $lineItem->getQuantity(),
            ];

            if (null !== $lineItem->getPrice()) {
                $detail['price'] = $this->currencyPrecision->getRoundedItemAmount(
                    $lineItem->getPrice()->getTotalPrice(),
                    $context->getCurrency(),
                );
            }

            $hashData['items'][] = \md5($this->serializer->encode($detail, JsonEncoder::FORMAT));
        }

        // sort hashed items to make sure, they are always in the same order (cart/order) cause Shopware will re-order
        // the items after placing the order for an unknown reason. If we collect the items and pass them directly into
        // the hash, the items may be in a different order, after the order has been placed --> validation will fail.
        \sort($hashData['items']);

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
            $hashData['birthday'] = $context->getCustomer()->getBirthday()->format(\DATE_W3C);
        }

        return $hashData;
    }

    private function generateHash(array $hashData): string
    {
        $json = $this->serializer->encode($hashData, JsonEncoder::FORMAT);

        if (empty($json)) {
            throw new \LogicException('could not generate hash');
        }

        if (empty($this->appSecret)) {
            throw new \LogicException('empty app secret');
        }

        return \hash_hmac('sha256', $json, $this->appSecret);
    }
}
