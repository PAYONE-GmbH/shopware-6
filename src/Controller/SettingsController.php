<?php

declare(strict_types=1);

namespace PayonePayment\Controller;

use DateInterval;
use DateTimeImmutable;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaypalPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSofortBankingPaymentHandler;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Test\TestRequestFactory;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class SettingsController extends AbstractController
{
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
     * @Route("/api/v{version}/_action/payone_payment/validate-api-credentials", name="api.action.payone_payment.validate.api.credentials", methods={"GET"})
     */
    public function validateApiCredentials(Request $request): JsonResponse
    {
        $salesChannelId = $request->get('salesChannelId');
        $errors         = [];

        foreach (ConfigurationPrefixes::CONFIGURATION_PREFIXES as $paymentClass => $configurationPrefix) {
            try {
                $testRequest = $this->requestFactory->getRequestParameters($salesChannelId, $configurationPrefix, $this->getPaymentParameters($paymentClass));

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
                    'reference'      => random_int(10000000, 99999999),
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
                    'reference'         => random_int(10000000, 99999999),
                    'firstname'         => 'Test',
                    'lastname'          => 'Test',
                    'country'           => 'DE',
                    'successurl'        => 'https://www.payone.com',
                ];
                break;
            case PayonePaypalPaymentHandler::class:
                return [
                    'request'      => 'preauthorization',
                    'clearingtype' => 'wlt',
                    'wallettype'   => 'PPE',
                    'amount'       => 100,
                    'currency'     => 'EUR',
                    'reference'    => random_int(10000000, 99999999),
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
                    'reference'              => random_int(10000000, 99999999),
                    'firstname'              => 'Test',
                    'lastname'               => 'Test',
                    'country'                => 'DE',
                    'successurl'             => 'https://www.payone.com',
                ];
                break;
            default:
                $this->logger->error(sprintf('There is no test data defined for payment class %s', $paymentClass));
                throw new RuntimeException(sprintf('There is no test data defined for payment class %s', $paymentClass));
                break;
        }
    }
}
