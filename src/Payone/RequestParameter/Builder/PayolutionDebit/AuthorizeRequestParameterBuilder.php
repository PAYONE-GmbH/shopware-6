<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\PayolutionDebit;

use DateTime;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\Helper\OrderFetcherInterface;
use PayonePayment\Installer\ConfigInstaller;
use PayonePayment\PaymentHandler\PayonePayolutionDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\ParameterBag;

class AuthorizeRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    /** @var ConfigReaderInterface */
    protected $configReader;

    /** @var OrderFetcherInterface */
    protected $orderFetcher;

    public function __construct(ConfigReaderInterface $configReader, OrderFetcherInterface $orderFetcher)
    {
        $this->configReader = $configReader;
        $this->orderFetcher = $orderFetcher;
    }

    /** @param PaymentTransactionStruct $arguments */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $dataBag             = $arguments->getRequestData();
        $salesChannelContext = $arguments->getSalesChannelContext();
        $paymentTransaction  = $arguments->getPaymentTransaction();

        $parameters = [
            'clearingtype'  => self::CLEARING_TYPE_FINANCING,
            'financingtype' => 'PYD',
            'request'       => self::REQUEST_ACTION_AUTHORIZE,
            'iban'          => $dataBag->get('payolutionIban'),
            'bic'           => $dataBag->get('payolutionBic'),
        ];

        $this->applyBirthdayParameter($parameters, $dataBag);

        if ($this->transferCompanyData($salesChannelContext)) {
            $this->provideCompanyParams($paymentTransaction->getOrder()->getId(), $parameters, $salesChannelContext->getContext());
        }

        return $parameters;
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();
        $action        = $arguments->getAction();

        return $paymentMethod === PayonePayolutionDebitPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
    }

    protected function applyBirthdayParameter(array &$parameters, ParameterBag $dataBag): void
    {
        if (!empty($dataBag->get('payolutionBirthday'))) {
            $birthday = DateTime::createFromFormat('Y-m-d', $dataBag->get('payolutionBirthday'));

            if (!empty($birthday)) {
                $parameters['birthday'] = $birthday->format('Ymd');
            }
        }
    }

    protected function transferCompanyData(SalesChannelContext $context): bool
    {
        $configuration = $this->configReader->read($context->getSalesChannel()->getId());

        return !empty($configuration->get(ConfigInstaller::CONFIG_FIELD_PAYOLUTION_DEBIT_TRANSFER_COMPANY_DATA));
    }

    protected function provideCompanyParams(string $orderId, array &$parameters, Context $context): void
    {
        $order = $this->orderFetcher->getOrderById($orderId, $context);

        if (null === $order) {
            return;
        }

        $orderAddresses = $order->getAddresses();

        if (null === $orderAddresses) {
            return;
        }

        /** @var OrderAddressEntity $billingAddress */
        $billingAddress = $orderAddresses->get($order->getBillingAddressId());

        if (!empty($billingAddress->getCompany()) && !empty($billingAddress->getVatId())) {
            $parameters['add_paydata[b2b]']         = 'yes';
            $parameters['add_paydata[company_uid]'] = $billingAddress->getVatId();

            return;
        }

        /** @var OrderCustomerEntity $orderCustomer */
        $orderCustomer = $order->getOrderCustomer();

        if (empty($orderCustomer->getCompany()) && empty($billingAddress->getCompany())) {
            return;
        }

        if (method_exists($orderCustomer, 'getVatIds') === false) {
            return;
        }

        $vatIds = $orderCustomer->getVatIds();

        if (empty($vatIds) === false) {
            $parameters['add_paydata[b2b]']         = 'yes';
            $parameters['add_paydata[company_uid]'] = $vatIds[0];
        }
    }
}
