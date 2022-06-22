<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\RatepayDebit;

use PayonePayment\Components\Helper\OrderFetcherInterface;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use RuntimeException;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\ParameterBag;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @var OrderFetcherInterface */
    protected $orderFetcher;

    public function __construct(OrderFetcherInterface $orderFetcher)
    {
        $this->orderFetcher = $orderFetcher;
    }

    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $dataBag             = $arguments->getRequestData();
        $salesChannelContext = $arguments->getSalesChannelContext();
        $paymentTransaction  = $arguments->getPaymentTransaction();

        $parameters = [
            'request'                                    => self::REQUEST_ACTION_AUTHORIZE,
            'clearingtype'                               => self::CLEARING_TYPE_FINANCING,
            'financingtype'                              => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPD,
            'iban'                                       => $dataBag->get('ratepayIban'),
            'add_paydata[customer_allow_credit_inquiry]' => 'yes',

            // ToDo: Ratepay Profile in der Administration pflegbar machen
            'add_paydata[shop_id]' => 88880103,
        ];

        $this->applyPhoneParameter(
            $paymentTransaction->getOrder()->getId(),
            $parameters,
            $dataBag,
            $salesChannelContext->getContext()
        );
        $this->applyBirthdayParameter($parameters, $dataBag);

        return $parameters;
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayoneRatepayDebitPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }

    protected function applyPhoneParameter(string $orderId, array &$parameters, ParameterBag $dataBag, Context $context): void
    {
        $order = $this->orderFetcher->getOrderById($orderId, $context);

        if (null === $order) {
            throw new RuntimeException('missing order');
        }

        $orderAddresses = $order->getAddresses();

        if (null === $orderAddresses) {
            throw new RuntimeException('missing order addresses');
        }

        /** @var OrderAddressEntity $billingAddress */
        $billingAddress = $orderAddresses->get($order->getBillingAddressId());

        if (null === $billingAddress) {
            throw new RuntimeException('missing order billing address');
        }

        $submittedPhoneNumber = $dataBag->get('ratepayPhone');

        if (!empty($submittedPhoneNumber) && $submittedPhoneNumber !== $billingAddress->getPhoneNumber()) {
            $billingAddress->setPhoneNumber($submittedPhoneNumber);
            // ToDo: Save billing address
        }

        if (!$billingAddress->getPhoneNumber()) {
            throw new RuntimeException('missing phone number');
        }

        $parameters['telephonenumber'] = $billingAddress->getPhoneNumber();
    }

    protected function applyBirthdayParameter(array &$parameters, ParameterBag $dataBag): void
    {
        if (!empty($dataBag->get('ratepayBirthday'))) {
            $birthday = \DateTime::createFromFormat('Y-m-d', $dataBag->get('ratepayBirthday'));

            if (!empty($birthday)) {
                $parameters['birthday'] = $birthday->format('Ymd');
            }
        }
    }
}
