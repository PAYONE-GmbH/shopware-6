<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder\RatepayDebit;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\Helper\OrderFetcherInterface;
use PayonePayment\PaymentHandler\AbstractPayonePaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
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
            'request'      => self::REQUEST_ACTION_AUTHORIZE,
            'clearingtype' => self::CLEARING_TYPE_FINANCING,
            'financingtype'   => AbstractPayonePaymentHandler::PAYONE_FINANCING_RPD,
            'iban'          => $dataBag->get('ratepayIban'),
            'bic'           => $dataBag->get('ratepayBic'),
            'telephonenumber' => $dataBag->get('ratepayPhone'),
            'add_paydata[customer_allow_credit_inquiry]' => 'yes',

            // ToDo: Ratepay Profile in der Administration pflegbar machen
            'add_paydata[shop_id]' => 88880103,
        ];

        $this->applyBirthdayParameter($parameters, $dataBag);

        if ($this->isConsideredB2B($salesChannelContext)) {
            $this->applyB2BParameters($paymentTransaction->getOrder()->getId(), $parameters, $salesChannelContext->getContext());
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

        return $paymentMethod === PayoneRatepayDebitPaymentHandler::class && $action === self::REQUEST_ACTION_AUTHORIZE;
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

    protected function isConsideredB2B(SalesChannelContext $context): bool
    {
        // ToDo: Wenn eine Company beim Kunden gesetzt ist, wird es für Ratepay als B2B gehandhabt
        return false;
    }

    protected function applyB2BParameters(string $orderId, array &$parameters, Context $context): void
    {
        // ToDo: Felder befüllen
        // Kein Pflichtfeld
        $parameters['add_paydata[vat_id]'] = null;
        $parameters['add_paydata[company_id]'] = null; // Handelsregisternummer

        // Pflichtfeld (falls B2B)
        $parameters['add_paydata[registry_location]'] = null;
        $parameters['add_paydata[registry_country_code]'] = null;
        $parameters['add_paydata[registry_city]'] = null;
        $parameters['add_paydata[registry_zip]'] = null;
        $parameters['add_paydata[registry_street]'] = null;
        $parameters['add_paydata[company_type]'] = null;
        $parameters['add_paydata[homepage]'] = null;
    }
}
