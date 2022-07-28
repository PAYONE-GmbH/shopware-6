<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\RatepayDebit;

use PayonePayment\Components\Helper\OrderFetcherInterface;
use PayonePayment\Components\Ratepay\Profile;
use PayonePayment\Components\Ratepay\ProfileServiceInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use RuntimeException;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @var OrderFetcherInterface */
    protected $orderFetcher;

    /** @var ProfileServiceInterface */
    protected $profileService;

    /** @var EntityRepositoryInterface */
    protected $customerRepository;

    public function __construct(
        OrderFetcherInterface $orderFetcher,
        ProfileServiceInterface $profileService,
        EntityRepositoryInterface $customerRepository
    ) {
        $this->orderFetcher       = $orderFetcher;
        $this->profileService     = $profileService;
        $this->customerRepository = $customerRepository;
    }

    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $dataBag             = $arguments->getRequestData();
        $salesChannelContext = $arguments->getSalesChannelContext();
        $paymentTransaction  = $arguments->getPaymentTransaction();
        $order               = $this->getOrder(
            $paymentTransaction->getOrder()->getId(),
            $salesChannelContext->getContext()
        );
        $profile = $this->getProfile($order);

        $parameters = [
            'request'                                    => self::REQUEST_ACTION_AUTHORIZE,
            'clearingtype'                               => self::CLEARING_TYPE_FINANCING,
            'financingtype'                              => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPD,
            'iban'                                       => $dataBag->get('ratepayIban'),
            'add_paydata[customer_allow_credit_inquiry]' => 'yes',
            'add_paydata[shop_id]'                       => $profile->getShopId(),
        ];

        $this->applyPhoneParameter($order, $parameters, $dataBag, $salesChannelContext->getContext());
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

    protected function getOrder(string $orderId, Context $context): OrderEntity
    {
        // Load order to make sure all associations are set
        $order = $this->orderFetcher->getOrderById($orderId, $context);

        if (null === $order) {
            throw new RuntimeException('missing order');
        }

        return $order;
    }

    protected function getProfile(OrderEntity $order): Profile
    {
        $profile = $this->profileService->getProfileByOrder($order, PayoneRatepayDebitPaymentHandler::class);

        if ($profile === null) {
            throw new RuntimeException('no ratepay profile found');
        }

        return $profile;
    }

    protected function applyPhoneParameter(OrderEntity $order, array &$parameters, ParameterBag $dataBag, Context $context): void
    {
        if (!$order->getOrderCustomer()) {
            throw new RuntimeException('missing order customer');
        }

        $customer = $order->getOrderCustomer()->getCustomer();

        if (!$customer) {
            throw new RuntimeException('missing customer');
        }

        $customerCustomFields = $customer->getCustomFields();

        if (!$customerCustomFields) {
            throw new RuntimeException('missing customer custom fields');
        }

        $submittedPhoneNumber = $dataBag->get('ratepayPhone');

        if (!empty($submittedPhoneNumber) && $submittedPhoneNumber !== $customerCustomFields[CustomFieldInstaller::CUSTOMER_PHONE_NUMBER]) {
            // Update the phone number that is stored at the customer
            $customerCustomFields[CustomFieldInstaller::CUSTOMER_PHONE_NUMBER] = $submittedPhoneNumber;
            $this->customerRepository->update(
                [
                    [
                        'id'           => $customer->getId(),
                        'customFields' => $customerCustomFields,
                    ],
                ],
                $context
            );
        }

        if (!$customerCustomFields[CustomFieldInstaller::CUSTOMER_PHONE_NUMBER]) {
            throw new RuntimeException('missing phone number');
        }

        $parameters['telephonenumber'] = $customerCustomFields[CustomFieldInstaller::CUSTOMER_PHONE_NUMBER];
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
