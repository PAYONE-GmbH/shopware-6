<?php

declare(strict_types=1);

namespace PayonePayment\Administration\Controller;

use PayonePayment\PaymentHandler as Handler;
use PayonePayment\PaymentMethod\PaymentMethodInterface;
use PayonePayment\PaymentMethod\PaymentMethodRegistry;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Provider\ApplePay\StoreApi\Route\ApplePayRoute;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use PayonePayment\RequestParameter\TestRequestParameterEnricher;
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
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    defaults: [
        '_routeScope' => [ 'api' ],
        '_acl'        => [ 'payone:configuration' ],
    ],
)]
class SettingsController extends AbstractController
{
    public function __construct(
        private readonly PayoneClientInterface $client,
        private readonly EntityRepository $stateMachineTransitionRepository,
        private readonly LoggerInterface $logger,
        private readonly string $kernelDirectory,
        private readonly EntityRepository $paymentMethodRepository,
        private readonly TestRequestParameterEnricher $requestParameterEnricher,
        private readonly RequestParameterEnricherChain $testEnrichers,
        private readonly PaymentMethodRegistry $paymentMethodRegistry,
    ) {
    }

    #[Route(
        path: '/api/_action/payone_payment/validate-api-credentials',
        name: 'api.action.payone_payment.validate.api.credentials',
        methods: [ 'POST' ],
    )]
    public function validateApiCredentials(Request $request, Context $context): JsonResponse
    {
        $testCount = 0;
        $errors    = [];
        
        /** @var PaymentMethodInterface $paymentMethod */
        foreach ($this->paymentMethodRegistry as $paymentMethod) {
            $paymentClass        = $paymentMethod->getPaymentHandlerClassName();
            $configurationPrefix = $paymentMethod::getConfigurationPrefix();

            /** @var PaymentMethodEntity|null $paymentMethod */
            $criteria      = (new Criteria())->addFilter(new EqualsFilter('handlerIdentifier', $paymentClass));
            $paymentMethod = $this->paymentMethodRepository->search($criteria, $context)->first();

            if (
                !$paymentMethod instanceof PaymentMethodEntity
                || !$paymentMethod->getActive()
                || \in_array($paymentMethod->getHandlerIdentifier(), Handler\PaymentHandlerGroups::RATEPAY, true)
            ) {
                continue;
            }

            ++$testCount;

            try {
                $parameters = \array_merge(
                    $this->getPaymentParameters($paymentClass),
                    $this->getConfigurationParameters($request, $paymentClass, $configurationPrefix),
                );

                $parameters['key'] = hash('sha384', (string) $parameters['key']);

                $this->client->request($parameters);
            } catch (PayoneRequestException $e) {
                $errors[$configurationPrefix] = $e->getResponse()['error']['ErrorMessage'];
            } catch (\Throwable $e) {
                $errors[$configurationPrefix] = true;
            }
        }

        $this->logger->info('payone plugin credentials validated', [
            'success' => [] === $errors,
            'results' => $errors,
        ]);

        return new JsonResponse([
            'testCount'        => $testCount,
            'credentialsValid' => [] === $errors,
            'errors'           => $errors,
        ]);
    }

    #[Route(
        path: '/api/_action/payone_payment/get-state-machine-transition-actions',
        name: 'api.action.payone_payment.get.state_machine_transition.actions',
        methods: [ 'GET' ],
    )]
    public function getStateMachineTransitionActions(Context $context): JsonResponse
    {
        $criteria = (new Criteria())
            ->addAssociation('stateMachine')
            ->addFilter(new EqualsFilter('stateMachine.technicalName', 'order_transaction.state'))
            ->addGroupField(new FieldGrouping('actionName'))
        ;

        $searchResult    = $this->stateMachineTransitionRepository->search($criteria, $context);
        $transitionNames = [];

        /** @var StateMachineTransitionEntity $stateMachineAction */
        foreach ($searchResult->getElements() as $stateMachineAction) {
            $transitionNames[] = [
                'label' => $stateMachineAction->getActionName(),
                'value' => $stateMachineAction->getActionName(),
            ];
        }

        return new JsonResponse([
            'data'  => $transitionNames,
            'total' => \count($transitionNames),
        ]);
    }

    #[Route(
        path: '/api/_action/payone_payment/check-apple-pay-cert',
        name: 'api.action.payone_payment.check.apple_pay_cert',
        methods: [ 'GET' ],
    )]
    public function checkApplePayCert(): JsonResponse
    {
        if (!\file_exists($this->kernelDirectory . ApplePayRoute::CERT_FOLDER . 'merchant_id.key')) {
            return new JsonResponse([ 'success' => false ], 404);
        }

        if (!\file_exists($this->kernelDirectory . ApplePayRoute::CERT_FOLDER . 'merchant_id.pem')) {
            return new JsonResponse([ 'success' => false ], 404);
        }

        return new JsonResponse(['success' => true], 200);
    }

    private function getPaymentParameters(string $paymentClass): array
    {
        $parameters = $this->requestParameterEnricher->enrich($paymentClass, $this->testEnrichers)?->all();

        if (null === $parameters) {
            $this->logger->error(\sprintf('There is no test data defined for payment class %s', $paymentClass));

            throw new \RuntimeException(\sprintf('There is no test data defined for payment class %s', $paymentClass));
        }

        return $parameters;
    }

    private function getConfigurationParameters(Request $request, string $paymentClass, string $configurationPrefix): array
    {
        $config = $request->get('credentials', []);

        if (!isset($config[$configurationPrefix])) {
            $this->logger->error(sprintf('There is no configuration for payment class %s', $paymentClass));

            throw new \RuntimeException(sprintf('There is no configuration for payment class %s', $paymentClass));
        }

        return [
            'aid'         => $config[$configurationPrefix]['accountId'],
            'mid'         => $config[$configurationPrefix]['merchantId'],
            'portalid'    => $config[$configurationPrefix]['portalId'],
            'key'         => $config[$configurationPrefix]['portalKey'],
            'api_version' => '3.10',
            'mode'        => 'test',
            'encoding'    => 'UTF-8',
        ];
    }
}
