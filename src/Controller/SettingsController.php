<?php

declare(strict_types=1);

namespace PayonePayment\Controller;

use DateInterval;
use DateTimeImmutable;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaypalExpressPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaypalPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;
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

    /** @var LoggerInterface */
    private $logger;

    public function __construct(PayoneClientInterface $client, TestRequestFactory $requestFactory, LoggerInterface $logger)
    {
        $this->client         = $client;
        $this->requestFactory = $requestFactory;
        $this->logger         = $logger;
    }

    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/v{version}/_action/payone_payment/validate-api-credentials", name="api.action.payone_payment.validate.api.credentials", methods={"POST"})
     */
    public function validateApiCredentials(Request $request, Context $context): JsonResponse
    {
        $errors = [];
        /** @var EntityRepositoryInterface $paymentMethodRepository */
        $paymentMethodRepository = $this->get('payment_method.repository');

        foreach (ConfigurationPrefixes::CONFIGURATION_PREFIXES as $paymentClass => $configurationPrefix) {
            /** @var null|PaymentMethodEntity $paymentMethod */
            $criteria      = (new Criteria())->addFilter(new EqualsFilter('handlerIdentifier', $paymentClass));
            $paymentMethod = $paymentMethodRepository->search($criteria, $context)->first();
            if (!$paymentMethod || !$paymentMethod->getActive()) {
                continue;
            }

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

        return new JsonResponse(['credentialsValid' => empty($errors), 'errors' => $errors]);
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
                break;
            case PayonePayolutionInstallmentPaymentHandler::class:
            case PayonePayolutionInvoicingPaymentHandler::class:
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
