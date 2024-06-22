<?php

declare(strict_types=1);

namespace PayonePayment\Controller;

use PayonePayment\Components\ApplePay\StoreApi\Route\ApplePayRoute;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\PaymentHandler as Handler;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\Builder\Amazon\AbstractAmazonRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\TestCredentialsStruct;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Grouping\FieldGrouping;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class SettingsController extends AbstractController
{
    private const REFERENCE_PREFIX_TEST = 'TESTPO-';

    public function __construct(
        private readonly PayoneClientInterface $client,
        private readonly RequestParameterFactory $requestFactory,
        private readonly EntityRepository $stateMachineTransitionRepository,
        private readonly LoggerInterface $logger,
        private readonly string $kernelDirectory,
        private readonly EntityRepository $paymentMethodRepository
    ) {
    }

    #[Route(path: '/api/_action/payone_payment/validate-api-credentials', name: 'api.action.payone_payment.validate.api.credentials', methods: ['POST'])]
    public function validateApiCredentials(Request $request, Context $context): JsonResponse
    {
        $testCount = 0;
        $errors = [];

        foreach (ConfigurationPrefixes::CONFIGURATION_PREFIXES as $paymentClass => $configurationPrefix) {
            /** @var PaymentMethodEntity|null $paymentMethod */
            $criteria = (new Criteria())->addFilter(new EqualsFilter('handlerIdentifier', $paymentClass));
            $paymentMethod = $this->paymentMethodRepository->search($criteria, $context)->first();

            if (!$paymentMethod instanceof PaymentMethodEntity
                || !$paymentMethod->getActive()
                || \in_array($paymentMethod->getHandlerIdentifier(), Handler\PaymentHandlerGroups::RATEPAY, true)) {
                continue;
            }

            ++$testCount;

            try {
                $parameters = array_merge($this->getPaymentParameters($paymentClass), $this->getConfigurationParameters($request, $paymentClass));
                $testRequest = $this->requestFactory->getRequestParameter(new TestCredentialsStruct($parameters, AbstractRequestParameterBuilder::REQUEST_ACTION_TEST, $paymentClass));

                $this->client->request($testRequest);
            } catch (PayoneRequestException $e) {
                $errors[$configurationPrefix] = $e->getResponse()['error']['ErrorMessage'];
            } catch (\Throwable) {
                $errors[$configurationPrefix] = true;
            }
        }

        $this->logger->info('payone plugin credentials validated', [
            'success' => empty($errors),
            'results' => $errors,
        ]);

        return new JsonResponse([
            'testCount' => $testCount,
            'credentialsValid' => empty($errors),
            'errors' => $errors,
        ]);
    }

    #[Route(path: '/api/_action/payone_payment/get-state-machine-transition-actions', name: 'api.action.payone_payment.get.state_machine_transition.actions', methods: ['GET'])]
    public function getStateMachineTransitionActions(Context $context): JsonResponse
    {
        $criteria = (new Criteria())
            ->addAssociation('stateMachine')
            ->addFilter(new EqualsFilter('stateMachine.technicalName', 'order_transaction.state'))
            ->addGroupField(new FieldGrouping('actionName'));

        $searchResult = $this->stateMachineTransitionRepository->search($criteria, $context);
        $transitionNames = [];

        /** @var StateMachineTransitionEntity $stateMachineAction */
        foreach ($searchResult->getElements() as $stateMachineAction) {
            $transitionNames[] = [
                'label' => $stateMachineAction->getActionName(),
                'value' => $stateMachineAction->getActionName(),
            ];
        }

        return new JsonResponse(['data' => $transitionNames, 'total' => \count($transitionNames)]);
    }

    #[Route(path: '/api/_action/payone_payment/check-apple-pay-cert', name: 'api.action.payone_payment.check.apple_pay_cert', methods: ['GET'])]
    public function checkApplePayCert(): JsonResponse
    {
        if (!file_exists($this->kernelDirectory . ApplePayRoute::CERT_FOLDER . 'merchant_id.key')) {
            return new JsonResponse(['success' => false], 404);
        }

        if (!file_exists($this->kernelDirectory . ApplePayRoute::CERT_FOLDER . 'merchant_id.pem')) {
            return new JsonResponse(['success' => false], 404);
        }

        return new JsonResponse(['success' => true], 200);
    }

    private function getPaymentParameters(string $paymentClass): array
    {
        switch ($paymentClass) {
            case Handler\PayoneCreditCardPaymentHandler::class:
                return [
                    'request' => 'preauthorization',
                    'clearingtype' => 'cc',
                    'amount' => 100,
                    'currency' => 'EUR',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'cardpan' => '5500000000000004',
                    'pseudocardpan' => '5500000000099999',
                    'cardtype' => 'M',
                    'cardexpiredate' => (new \DateTimeImmutable())->add(new \DateInterval('P1Y'))->format('ym'),
                    'ecommercemode' => 'internet',
                    'firstname' => 'Test',
                    'lastname' => 'Test',
                    'country' => 'DE',
                    'successurl' => 'https://www.payone.com',
                ];

            case Handler\PayoneDebitPaymentHandler::class:
                return [
                    'request' => 'preauthorization',
                    'clearingtype' => 'elv',
                    'iban' => 'DE00123456782599100003',
                    'bic' => 'TESTTEST',
                    'bankaccountholder' => 'Test Test',
                    'amount' => 100,
                    'currency' => 'EUR',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'firstname' => 'Test',
                    'lastname' => 'Test',
                    'country' => 'DE',
                    'successurl' => 'https://www.payone.com',
                ];

            case Handler\PayonePaypalExpressPaymentHandler::class:
            case Handler\PayonePaypalPaymentHandler::class:
                return [
                    'request' => 'preauthorization',
                    'clearingtype' => 'wlt',
                    'wallettype' => 'PPE',
                    'amount' => 100,
                    'currency' => 'EUR',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'firstname' => 'Test',
                    'lastname' => 'Test',
                    'country' => 'DE',
                    'successurl' => 'https://www.payone.com',
                ];

            case Handler\PayoneSofortBankingPaymentHandler::class:
                return [
                    'request' => 'preauthorization',
                    'clearingtype' => 'sb',
                    'onlinebanktransfertype' => 'PNT',
                    'bankcountry' => 'DE',
                    'amount' => 100,
                    'currency' => 'EUR',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'firstname' => 'Test',
                    'lastname' => 'Test',
                    'country' => 'DE',
                    'successurl' => 'https://www.payone.com',
                ];

            case Handler\PayoneEpsPaymentHandler::class:
                return [
                    'request' => 'preauthorization',
                    'clearingtype' => 'sb',
                    'onlinebanktransfertype' => 'EPS',
                    'bankcountry' => 'AT',
                    'bankgrouptype' => 'ARZ_HTB',
                    'amount' => 100,
                    'currency' => 'EUR',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'firstname' => 'Test',
                    'lastname' => 'Test',
                    'country' => 'AT',
                    'successurl' => 'https://www.payone.com',
                ];

            case Handler\PayoneIDealPaymentHandler::class:
                return [
                    'request' => 'preauthorization',
                    'clearingtype' => 'sb',
                    'onlinebanktransfertype' => 'IDL',
                    'bankcountry' => 'NL',
                    'bankgrouptype' => 'ING_BANK',
                    'amount' => 100,
                    'currency' => 'EUR',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'firstname' => 'Test',
                    'lastname' => 'Test',
                    'country' => 'NL',
                    'successurl' => 'https://www.payone.com',
                ];

            case Handler\PayoneBancontactPaymentHandler::class:
                return [
                    'request' => 'preauthorization',
                    'clearingtype' => 'sb',
                    'onlinebanktransfertype' => 'BCT',
                    'bankcountry' => 'BE',
                    'amount' => 100,
                    'currency' => 'EUR',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'lastname' => 'Test',
                    'country' => 'BE',
                    'successurl' => 'https://www.payone.com',
                    'errorurl' => 'https://www.payone.com',
                    'backurl' => 'https://www.payone.com',
                ];

            case Handler\PayonePayolutionInvoicingPaymentHandler::class:
                return [
                    'request' => 'genericpayment',
                    'clearingtype' => 'fnc',
                    'financingtype' => 'PYV',
                    'add_paydata[action]' => 'pre_check',
                    'add_paydata[payment_type]' => 'Payolution-Invoicing',
                    'amount' => 10000,
                    'currency' => 'EUR',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'birthday' => '19900505',
                    'firstname' => 'Test',
                    'lastname' => 'Test',
                    'country' => 'DE',
                    'email' => 'test@example.com',
                    'street' => 'teststreet 2',
                    'zip' => '12345',
                    'city' => 'Test',
                    'ip' => '127.0.0.1',
                ];

            case Handler\PayonePayolutionDebitPaymentHandler::class:
                return [
                    'request' => 'genericpayment',
                    'clearingtype' => 'fnc',
                    'financingtype' => 'PYD',
                    'add_paydata[action]' => 'pre_check',
                    'add_paydata[payment_type]' => 'Payolution-Debit',
                    'amount' => 10000,
                    'currency' => 'EUR',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'birthday' => '19900505',
                    'firstname' => 'Test',
                    'lastname' => 'Test',
                    'country' => 'DE',
                    'email' => 'test@example.com',
                    'street' => 'teststreet 2',
                    'zip' => '12345',
                    'city' => 'Test',
                    'ip' => '127.0.0.1',
                    'iban' => 'DE00123456782599100004',
                    'bic' => 'TESTTEST',
                ];

            case Handler\PayonePayolutionInstallmentPaymentHandler::class:
                return [
                    'request' => 'genericpayment',
                    'clearingtype' => 'fnc',
                    'financingtype' => 'PYS',
                    'add_paydata[action]' => 'pre_check',
                    'add_paydata[payment_type]' => 'Payolution-Installment',
                    'amount' => 10000,
                    'currency' => 'EUR',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'birthday' => '19900505',
                    'firstname' => 'Test',
                    'lastname' => 'Test',
                    'country' => 'DE',
                    'email' => 'test@example.com',
                    'street' => 'teststreet 2',
                    'zip' => '12345',
                    'city' => 'Test',
                    'ip' => '127.0.0.1',
                ];

            case Handler\PayonePrepaymentPaymentHandler::class:
                return [
                    'request' => 'preauthorization',
                    'clearingtype' => 'vor',
                    'amount' => 10000,
                    'currency' => 'EUR',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'firstname' => 'Test',
                    'lastname' => 'Test',
                    'country' => 'DE',
                    'email' => 'test@example.com',
                    'street' => 'teststreet 2',
                    'zip' => '12345',
                    'city' => 'Test',
                    'ip' => '127.0.0.1',
                ];

            case Handler\PayoneTrustlyPaymentHandler::class:
                return [
                    'request' => 'preauthorization',
                    'clearingtype' => 'sb',
                    'onlinebanktransfertype' => 'TRL',
                    'iban' => 'DE00123456782599100004',
                    'amount' => 100,
                    'currency' => 'EUR',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'firstname' => 'Test',
                    'lastname' => 'Test',
                    'country' => 'DE',
                    'successurl' => 'https://www.payone.com',
                ];

            case Handler\PayoneSecureInvoicePaymentHandler::class:
                return [
                    'request' => 'preauthorization',
                    'clearingtype' => 'rec',
                    'financingtype' => 'POV',
                    'amount' => 10000,
                    'currency' => 'EUR',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'birthday' => '19900505',
                    'firstname' => 'Test',
                    'lastname' => 'Test',
                    'country' => 'DE',
                    'email' => 'test@example.com',
                    'street' => 'teststreet 2',
                    'zip' => '12345',
                    'city' => 'Test',
                    'ip' => '127.0.0.1',
                    'businessrelation' => 'b2c',
                ];

            case Handler\PayoneOpenInvoicePaymentHandler::class:
                return [
                    'request' => 'preauthorization',
                    'clearingtype' => 'rec',
                    'amount' => 10000,
                    'currency' => 'EUR',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'birthday' => '19900505',
                    'firstname' => 'Test',
                    'lastname' => 'Test',
                    'country' => 'DE',
                    'email' => 'test@example.com',
                    'street' => 'teststreet 2',
                    'zip' => '12345',
                    'city' => 'Test',
                    'ip' => '127.0.0.1',
                    'businessrelation' => 'b2c',
                ];

            case Handler\PayonePaydirektPaymentHandler::class:
                return [
                    'request' => 'genericpayment',
                    'clearingtype' => 'wlt',
                    'wallettype' => 'PDT',
                    'amount' => 10000,
                    'currency' => 'EUR',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'add_paydata[action]' => 'checkout',
                    'add_paydata[type]' => 'order',
                    'add_paydata[web_url_shipping_terms]' => 'https://www.payone.com',
                    'successurl' => 'https://www.payone.com',
                    'backurl' => 'https://www.payone.com',
                    'errorurl' => 'https://www.payone.com',
                ];

            case Handler\PayoneApplePayPaymentHandler::class:
                //TODO: Test request for apple pay is failing because of missing token params, we will use prepayment request to validate specific merchant data
                return [
                    'request' => 'preauthorization',
                    'clearingtype' => 'vor',
                    'amount' => 10000,
                    'currency' => 'EUR',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'firstname' => 'Test',
                    'lastname' => 'Test',
                    'country' => 'DE',
                    'email' => 'test@example.com',
                    'street' => 'teststreet 2',
                    'zip' => '12345',
                    'city' => 'Test',
                    'ip' => '127.0.0.1',
                ];
            case Handler\PayoneKlarnaInvoicePaymentHandler::class:
            case Handler\PayoneKlarnaDirectDebitPaymentHandler::class:
            case Handler\PayoneKlarnaInstallmentPaymentHandler::class:
                return [
                    'request' => AbstractRequestParameterBuilder::REQUEST_ACTION_GENERIC_PAYMENT,
                    'clearingtype' => AbstractRequestParameterBuilder::CLEARING_TYPE_FINANCING,
                    'amount' => 100,
                    'country' => 'DE',
                    'currency' => 'EUR',
                    'add_paydata[action]' => 'start_session',
                    'it[1]' => 'goods',
                    'id[1]' => '5013210425384',
                    'pr[1]' => 100,
                    'de[1]' => 'Test product',
                    'no[1]' => 1,
                ];

            case Handler\PayonePrzelewy24PaymentHandler::class:
                return [
                    'request' => 'preauthorization',
                    'clearingtype' => 'sb',
                    'onlinebanktransfertype' => 'P24',
                    'bankcountry' => 'PL',
                    'amount' => 100,
                    'currency' => 'EUR',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'lastname' => 'Test',
                    'country' => 'PL',
                    'successurl' => 'https://www.payone.com',
                    'errorurl' => 'https://www.payone.com',
                    'backurl' => 'https://www.payone.com',
                ];

            case Handler\PayoneWeChatPayPaymentHandler::class:
                return [
                    'request' => 'preauthorization',
                    'clearingtype' => 'wlt',
                    'wallettype' => 'WCP',
                    'amount' => 100,
                    'country' => 'DE',
                    'currency' => 'EUR',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'lastname' => 'Test',
                    'successurl' => 'https://www.payone.com',
                    'errorurl' => 'https://www.payone.com',
                    'backurl' => 'https://www.payone.com',
                ];

            case Handler\PayonePostfinanceCardPaymentHandler::class:
                return [
                    'request' => AbstractRequestParameterBuilder::REQUEST_ACTION_GENERIC_PAYMENT,
                    'add_paydata[action]' => 'register_alias',
                    'clearingtype' => AbstractRequestParameterBuilder::CLEARING_TYPE_ONLINE_BANK_TRANSFER,
                    'onlinebanktransfertype' => \PayonePayment\Payone\RequestParameter\Builder\Postfinance\AbstractRequestParameterBuilder::ONLINEBANK_TRANSFER_TYPE_CARD,
                    'bankcountry' => 'CH',
                    'amount' => 100,
                    'currency' => 'CHF',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'lastname' => 'Test',
                    'country' => 'CH',
                    'successurl' => 'https://www.payone.com',
                    'errorurl' => 'https://www.payone.com',
                    'backurl' => 'https://www.payone.com',
                ];
            case Handler\PayonePostfinanceWalletPaymentHandler::class:
                return [
                    'request' => AbstractRequestParameterBuilder::REQUEST_ACTION_GENERIC_PAYMENT,
                    'add_paydata[action]' => 'register_alias',
                    'clearingtype' => AbstractRequestParameterBuilder::CLEARING_TYPE_ONLINE_BANK_TRANSFER,
                    'onlinebanktransfertype' => \PayonePayment\Payone\RequestParameter\Builder\Postfinance\AbstractRequestParameterBuilder::ONLINEBANK_TRANSFER_TYPE_WALLET,
                    'bankcountry' => 'CH',
                    'amount' => 100,
                    'currency' => 'CHF',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'lastname' => 'Test',
                    'country' => 'CH',
                    'successurl' => 'https://www.payone.com',
                    'errorurl' => 'https://www.payone.com',
                    'backurl' => 'https://www.payone.com',
                ];

            case Handler\PayoneAlipayPaymentHandler::class:
                return [
                    'request' => 'preauthorization',
                    'clearingtype' => 'wlt',
                    'wallettype' => 'ALP',
                    'amount' => 100,
                    'country' => 'DE',
                    'currency' => 'EUR',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'lastname' => 'Test',
                    'successurl' => 'https://www.payone.com',
                    'errorurl' => 'https://www.payone.com',
                    'backurl' => 'https://www.payone.com',
                ];

            case Handler\PayoneSecuredInvoicePaymentHandler::class:
                return [
                    'request' => 'preauthorization',
                    'clearingtype' => 'fnc',
                    'financingtype' => 'PIV',
                    'mode' => 'test',
                    'telephonenumber' => '49304658976',
                    'birthday' => '20000101',
                    'businessrelation' => 'b2c',
                    'amount' => 10000,
                    'currency' => 'EUR',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'email' => 'test@example.com',
                    'firstname' => 'Test',
                    'lastname' => 'Test',
                    'country' => 'DE',
                    'city' => 'Berlin',
                    'street' => 'Mustergasse 5',
                    'zip' => '10969',
                    'it[1]' => 'goods',
                    'id[1]' => '5013210425384',
                    'pr[1]' => 10000,
                    'de[1]' => 'Test product',
                    'no[1]' => 1,
                    'va[1]' => 19,
                    'shipping_city' => 'Berlin',
                    'shipping_country' => 'DE',
                    'shipping_firstname' => 'Test',
                    'shipping_lastname' => 'Test',
                    'shipping_street' => 'Mustergasse 5',
                    'shipping_zip' => '10969',
                    'successurl' => 'https://www.payone.com',
                    'errorurl' => 'https://www.payone.com',
                    'backurl' => 'https://www.payone.com',
                ];

            case Handler\PayoneSecuredInstallmentPaymentHandler::class:
                return [
                    'request' => 'preauthorization',
                    'clearingtype' => 'fnc',
                    'financingtype' => 'PIN',
                    'mode' => 'test',
                    'telephonenumber' => '49304658976',
                    'birthday' => '20000101',
                    'businessrelation' => 'b2c',
                    'amount' => 30000,
                    'currency' => 'EUR',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'email' => 'test@example.com',
                    'bankaccountholder' => 'Test Test',
                    'iban' => 'DE62500105171314583819',
                    'add_paydata[installment_option_id]' => 'IOP_bbc08f0a1b2a41268048b41e2efb31a4',
                    'firstname' => 'Test',
                    'lastname' => 'Test',
                    'country' => 'DE',
                    'city' => 'Berlin',
                    'street' => 'Mustergasse 5',
                    'zip' => '10969',
                    'it[1]' => 'goods',
                    'id[1]' => '5013210425384',
                    'pr[1]' => 30000,
                    'de[1]' => 'Test product',
                    'no[1]' => 1,
                    'va[1]' => 19,
                    'shipping_city' => 'Berlin',
                    'shipping_country' => 'DE',
                    'shipping_firstname' => 'Test',
                    'shipping_lastname' => 'Test',
                    'shipping_street' => 'Mustergasse 5',
                    'shipping_zip' => '10969',
                    'successurl' => 'https://www.payone.com',
                    'errorurl' => 'https://www.payone.com',
                    'backurl' => 'https://www.payone.com',
                ];

            case Handler\PayoneSecuredDirectDebitPaymentHandler::class:
                return [
                    'request' => 'preauthorization',
                    'clearingtype' => 'fnc',
                    'financingtype' => 'PDD',
                    'mode' => 'test',
                    'telephonenumber' => '49304658976',
                    'birthday' => '20000101',
                    'businessrelation' => 'b2c',
                    'amount' => 10000,
                    'currency' => 'EUR',
                    'reference' => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1_000_000_000_000, 9_999_999_999_999)),
                    'email' => 'test@example.com',
                    'bankaccountholder' => 'Test Test',
                    'iban' => 'DE62500105171314583819',
                    'firstname' => 'Test',
                    'lastname' => 'Test',
                    'country' => 'DE',
                    'city' => 'Berlin',
                    'street' => 'Mustergasse 5',
                    'zip' => '10969',
                    'it[1]' => 'goods',
                    'id[1]' => '5013210425384',
                    'pr[1]' => 10000,
                    'de[1]' => 'Test product',
                    'no[1]' => 1,
                    'va[1]' => 19,
                    'shipping_city' => 'Berlin',
                    'shipping_country' => 'DE',
                    'shipping_firstname' => 'Test',
                    'shipping_lastname' => 'Test',
                    'shipping_street' => 'Mustergasse 5',
                    'shipping_zip' => '10969',
                    'successurl' => 'https://www.payone.com',
                    'errorurl' => 'https://www.payone.com',
                    'backurl' => 'https://www.payone.com',
                ];

            case Handler\PayoneAmazonPayExpressPaymentHandler::class:
            case Handler\PayoneAmazonPayPaymentHandler::class:
                return [
                    'request' => AbstractRequestParameterBuilder::REQUEST_ACTION_GENERIC_PAYMENT,
                    'add_paydata[action]' => 'createCheckoutSessionPayload',
                    'clearingtype' => AbstractAmazonRequestParameterBuilder::CLEARING_TYPE,
                    'wallettype' => AbstractAmazonRequestParameterBuilder::WALLET_TYPE,
                    'amount' => 100,
                    'currency' => 'EUR',
                    'successurl' => 'https://www.payone.com',
                    'errorurl' => 'https://www.payone.com',
                    'backurl' => 'https://www.payone.com',
                ];

            default:
                $this->logger->error(sprintf('There is no test data defined for payment class %s', $paymentClass));

                throw new \RuntimeException(sprintf('There is no test data defined for payment class %s', $paymentClass));
        }
    }

    private function getConfigurationParameters(Request $request, string $paymentClass): array
    {
        $config = $request->get('credentials', []);
        $prefix = ConfigurationPrefixes::CONFIGURATION_PREFIXES[$paymentClass];

        if (!isset($config[$prefix])) {
            $this->logger->error(sprintf('There is no configuration for payment class %s', $paymentClass));

            throw new \RuntimeException(sprintf('There is no configuration for payment class %s', $paymentClass));
        }

        return [
            'aid' => $config[$prefix]['accountId'],
            'mid' => $config[$prefix]['merchantId'],
            'portalid' => $config[$prefix]['portalId'],
            'key' => $config[$prefix]['portalKey'],
            'api_version' => '3.10',
            'mode' => 'test',
            'encoding' => 'UTF-8',
        ];
    }
}
