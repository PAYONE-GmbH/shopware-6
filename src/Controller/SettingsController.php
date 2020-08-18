<?php

declare(strict_types=1);

namespace PayonePayment\Controller;

use DateInterval;
use DateTimeImmutable;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneEpsPaymentHandler;
use PayonePayment\PaymentHandler\PayoneIDealPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaypalExpressPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaypalPaymentHandler;
use PayonePayment\PaymentHandler\PayonePrepaymentPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSofortBankingPaymentHandler;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Test\TestRequestFactory;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Grouping\FieldGrouping;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class SettingsController extends AbstractController
{
    private const REFERENCE_PREFIX_TEST = 'TESTPO-';

    /** @var PayoneClientInterface */
    private $client;

    /** @var TestRequestFactory */
    private $requestFactory;

    /** @var EntityRepositoryInterface */
    private $stateMachineTransitionRepository;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        PayoneClientInterface $client,
        TestRequestFactory $requestFactory,
        EntityRepositoryInterface $stateMachineTransitionRepository,
        LoggerInterface $logger
    ) {
        $this->client                           = $client;
        $this->requestFactory                   = $requestFactory;
        $this->stateMachineTransitionRepository = $stateMachineTransitionRepository;
        $this->logger                           = $logger;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/v{version}/_action/payone_payment/validate-api-credentials", name="api.action.payone_payment.validate.api.credentials", methods={"POST"})
     */
    public function validateApiCredentials(Request $request, Context $context): JsonResponse
    {
        $testCount = 0;
        $errors    = [];

        /** @var EntityRepositoryInterface $paymentMethodRepository */
        $paymentMethodRepository = $this->get('payment_method.repository');

        foreach (ConfigurationPrefixes::CONFIGURATION_PREFIXES as $paymentClass => $configurationPrefix) {
            /** @var null|PaymentMethodEntity $paymentMethod */
            $criteria      = (new Criteria())->addFilter(new EqualsFilter('handlerIdentifier', $paymentClass));
            $paymentMethod = $paymentMethodRepository->search($criteria, $context)->first();

            if (!$paymentMethod || !$paymentMethod->getActive()) {
                continue;
            }

            ++$testCount;

            try {
                $parameters  = array_merge($this->getPaymentParameters($paymentClass), $this->getConfigurationParameters($request, $paymentClass));
                $testRequest = $this->requestFactory->getRequestParameters($parameters);

                $this->client->request($testRequest);
            } catch (PayoneRequestException $exception) {
                $errors[$configurationPrefix] = true;
            } catch (Throwable $exception) {
                $errors[$configurationPrefix] = true;
            }
        }

        $this->logger->info('payone plugin credentials validated', [
            'success' => empty($errors),
            'results' => $errors,
        ]);

        return new JsonResponse([
            'testCount'        => $testCount,
            'credentialsValid' => empty($errors),
            'errors'           => $errors,
        ]);
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/v{version}/_action/payone_payment/get-state-machine-transition-actions", name="api.action.payone_payment.get.state_machine_transition.actions", methods={"GET"})
     */
    public function getStateMachineTransitionActions(Request $request, Context $context): JsonResponse
    {
        $criteria = (new Criteria())
            ->addAssociation('stateMachine')
            ->addFilter(new EqualsFilter('stateMachine.technicalName', 'order_transaction.state'))
            ->addGroupField(new FieldGrouping('actionName'));

        $searchResult    = $this->stateMachineTransitionRepository->search($criteria, $context);
        $transitionNames = [];

        if (count($searchResult->getElements()) > 0) {
            foreach ($searchResult->getElements() as $stateMachineAction) {
                $transitionNames[] = [
                    'label' => $stateMachineAction->getActionName(),
                    'value' => $stateMachineAction->getActionName(),
                ];
            }
        }

        return new JsonResponse(['data' => $transitionNames, 'total' => count($transitionNames)]);
    }

    private function getPaymentParameters(string $paymentClass): array
    {
        switch ($paymentClass) {
            case PayoneCreditCardPaymentHandler::class:
                return [
                    'request'        => 'preauthorization',
                    'clearingtype'   => 'cc',
                    'amount'         => 100,
                    'currency'       => 'EUR',
                    'reference'      => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'cardpan'        => '5500000000000004',
                    'pseudocardpan'  => '5500000000099999',
                    'cardtype'       => 'M',
                    'cardexpiredate' => (new DateTimeImmutable())->add(new DateInterval('P1Y'))->format('ym'),
                    'ecommercemode'  => 'internet',
                    'firstname'      => 'Test',
                    'lastname'       => 'Test',
                    'country'        => 'DE',
                    'successurl'     => 'https://www.payone.com',
                ];

                break;
            case PayoneDebitPaymentHandler::class:
                return [
                    'request'           => 'preauthorization',
                    'clearingtype'      => 'elv',
                    'iban'              => 'DE00123456782599100003',
                    'bic'               => 'TESTTEST',
                    'bankaccountholder' => 'Test Test',
                    'amount'            => 100,
                    'currency'          => 'EUR',
                    'reference'         => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'firstname'         => 'Test',
                    'lastname'          => 'Test',
                    'country'           => 'DE',
                    'successurl'        => 'https://www.payone.com',
                ];

                break;
            case PayonePaypalExpressPaymentHandler::class:
            case PayonePaypalPaymentHandler::class:
                return [
                    'request'      => 'preauthorization',
                    'clearingtype' => 'wlt',
                    'wallettype'   => 'PPE',
                    'amount'       => 100,
                    'currency'     => 'EUR',
                    'reference'    => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'firstname'    => 'Test',
                    'lastname'     => 'Test',
                    'country'      => 'DE',
                    'successurl'   => 'https://www.payone.com',
                ];

                break;
            case PayoneSofortBankingPaymentHandler::class:
                return [
                    'request'                => 'preauthorization',
                    'clearingtype'           => 'sb',
                    'onlinebanktransfertype' => 'PNT',
                    'bankcountry'            => 'DE',
                    'amount'                 => 100,
                    'currency'               => 'EUR',
                    'reference'              => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'firstname'              => 'Test',
                    'lastname'               => 'Test',
                    'country'                => 'DE',
                    'successurl'             => 'https://www.payone.com',
                ];

            case PayoneEpsPaymentHandler::class:
                return [
                    'request'                => 'preauthorization',
                    'clearingtype'           => 'sb',
                    'onlinebanktransfertype' => 'EPS',
                    'bankcountry'            => 'AT',
                    'bankgrouptype'          => 'ARZ_HTB',
                    'amount'                 => 100,
                    'currency'               => 'EUR',
                    'reference'              => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'firstname'              => 'Test',
                    'lastname'               => 'Test',
                    'country'                => 'AT',
                    'successurl'             => 'https://www.payone.com',
                ];

            case PayoneIDealPaymentHandler::class:
                return [
                    'request'                => 'preauthorization',
                    'clearingtype'           => 'sb',
                    'onlinebanktransfertype' => 'IDL',
                    'bankcountry'            => 'NL',
                    'bankgrouptype'          => 'ING_BANK',
                    'amount'                 => 100,
                    'currency'               => 'EUR',
                    'reference'              => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'firstname'              => 'Test',
                    'lastname'               => 'Test',
                    'country'                => 'NL',
                    'successurl'             => 'https://www.payone.com',
                ];

                break;
            case PayonePayolutionInvoicingPaymentHandler::class:
                return [
                    'request'                   => 'genericpayment',
                    'clearingtype'              => 'fnc',
                    'financingtype'             => 'PYV',
                    'add_paydata[action]'       => 'pre_check',
                    'add_paydata[payment_type]' => 'Payolution-Invoicing',
                    'amount'                    => 10000,
                    'currency'                  => 'EUR',
                    'reference'                 => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'birthday'                  => '19900505',
                    'firstname'                 => 'Test',
                    'lastname'                  => 'Test',
                    'country'                   => 'DE',
                    'email'                     => 'test@example.com',
                    'street'                    => 'teststreet 2',
                    'zip'                       => '12345',
                    'city'                      => 'Test',
                    'ip'                        => '127.0.0.1',
                ];

                break;

            case PayonePayolutionDebitPaymentHandler::class:
                return [
                    'request'                   => 'genericpayment',
                    'clearingtype'              => 'fnc',
                    'financingtype'             => 'PYD',
                    'add_paydata[action]'       => 'pre_check',
                    'add_paydata[payment_type]' => 'Payolution-Debit',
                    'amount'                    => 10000,
                    'currency'                  => 'EUR',
                    'reference'                 => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'birthday'                  => '19900505',
                    'firstname'                 => 'Test',
                    'lastname'                  => 'Test',
                    'country'                   => 'DE',
                    'email'                     => 'test@example.com',
                    'street'                    => 'teststreet 2',
                    'zip'                       => '12345',
                    'city'                      => 'Test',
                    'ip'                        => '127.0.0.1',
                    'iban'                      => 'DE00123456782599100004',
                    'bic'                       => 'TESTTEST',
                ];

            case PayonePayolutionInstallmentPaymentHandler::class:
                return [
                    'request'                   => 'genericpayment',
                    'clearingtype'              => 'fnc',
                    'financingtype'             => 'PYS',
                    'add_paydata[action]'       => 'pre_check',
                    'add_paydata[payment_type]' => 'Payolution-Installment',
                    'amount'                    => 10000,
                    'currency'                  => 'EUR',
                    'reference'                 => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'birthday'                  => '19900505',
                    'firstname'                 => 'Test',
                    'lastname'                  => 'Test',
                    'country'                   => 'DE',
                    'email'                     => 'test@example.com',
                    'street'                    => 'teststreet 2',
                    'zip'                       => '12345',
                    'city'                      => 'Test',
                    'ip'                        => '127.0.0.1',
                ];

                break;

            case PayonePrepaymentPaymentHandler::class:
                return [
                    'request'      => 'preauthorization',
                    'clearingtype' => 'vor',
                    'amount'       => 10000,
                    'currency'     => 'EUR',
                    'reference'    => sprintf('%s%d', self::REFERENCE_PREFIX_TEST, random_int(1000000000000, 9999999999999)),
                    'firstname'    => 'Test',
                    'lastname'     => 'Test',
                    'country'      => 'DE',
                    'email'        => 'test@example.com',
                    'street'       => 'teststreet 2',
                    'zip'          => '12345',
                    'city'         => 'Test',
                    'ip'           => '127.0.0.1',
                ];

                break;
            default:
                $this->logger->error(sprintf('There is no test data defined for payment class %s', $paymentClass));
                throw new RuntimeException(sprintf('There is no test data defined for payment class %s', $paymentClass));

                break;
        }
    }

    private function getConfigurationParameters(Request $request, string $paymentClass): array
    {
        $config = $request->get('credentials', []);
        $prefix = ConfigurationPrefixes::CONFIGURATION_PREFIXES[$paymentClass];

        if (!isset($config[$prefix])) {
            $this->logger->error(sprintf('There is no configuration for payment class %s', $paymentClass));

            throw new RuntimeException(sprintf('There is no configuration for payment class %s', $paymentClass));
        }

        return [
            'aid'         => $config[$prefix]['accountId'],
            'mid'         => $config[$prefix]['merchantId'],
            'portalid'    => $config[$prefix]['portalId'],
            'key'         => $config[$prefix]['portalKey'],
            'api_version' => '3.10',
            'mode'        => 'test',
            'encoding'    => 'UTF-8',
        ];
    }
}
