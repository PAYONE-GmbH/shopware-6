<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\ConfigReader\ConfigReaderInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Read\ReadCriteria;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\System\Language\LanguageEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Router;

class PayonePaypalPaymentHandler implements PaymentHandlerInterface
{
    /** @var RepositoryInterface */
    private $transactionRepository;

    /** @var RepositoryInterface */
    private $orderCustomerRepository;

    /** @var RepositoryInterface */
    private $languageRepository;

    /** @var RequestStack */
    private $requestStack;

    /** @var Router */
    private $router;

    /**
     * @var ConfigReaderInterface
     */
    private $configReader;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        RepositoryInterface $transactionRepository,
        RepositoryInterface $orderCustomerRepository,
        RepositoryInterface $languageRepository,
        RequestStack $requestStack,
        Router $router,
        ConfigReaderInterface $configReader,
        LoggerInterface $logger
    ) {
        $this->transactionRepository   = $transactionRepository;
        $this->orderCustomerRepository = $orderCustomerRepository;
        $this->languageRepository      = $languageRepository;
        $this->requestStack            = $requestStack;
        $this->router                  = $router;
        $this->configReader = $configReader;
        $this->logger                  = $logger;
    }

    public function pay(PaymentTransactionStruct $transaction, Context $context): ?RedirectResponse
    {
        $response = $this->authorizePayment($transaction, $context);

        $this->savePayoneResponseData($transaction, $context, $response);

        if (!empty($response['Status']) && $response['Status'] === 'REDIRECT') {
            return new RedirectResponse($response['RedirectUrl']);
        }

        // TODO: For other payment Methods this path is valid, for paypal this should not happen so handle errors
        // TODO: if not an error and the transaction is actually approved, redirect to the return url and finish the payment
        // TODO: a status call should arrive

        return new RedirectResponse($transaction->getReturnUrl());
    }

    public function finalize(string $transactionId, Request $request, Context $context): void
    {
        $data = [
            'id'                      => $transactionId,
            'orderTransactionStateId' => Defaults::ORDER_TRANSACTION_COMPLETED,
        ];

        $this->transactionRepository->update([$data], $context);
    }

    private function getPayonePersonalData(PaymentTransactionStruct $transaction, Context $context): array
    {
        $criteria = new ReadCriteria([$transaction->getOrder()->getOrderCustomerId()]);
        $criteria->addAssociation('order_customer.customer');

        /** @var OrderCustomerEntity $orderCustomer */
        $orderCustomer = $this->orderCustomerRepository->read($criteria, $context)->first();

        $languages = $context->getLanguageIdChain();
        $criteria  = new ReadCriteria([reset($languages)]);
        $criteria->addAssociation('language.locale');

        /** @var LanguageEntity $language */
        $language = $this->languageRepository->read($criteria, $context)->first();

        $personalData = [
            'salutation'      => $transaction->getOrder()->getBillingAddress()->getSalutation(),
            'title'           => $transaction->getOrder()->getBillingAddress()->getTitle(),
            'firstname'       => $transaction->getOrder()->getBillingAddress()->getFirstName(),
            'lastname'        => $transaction->getOrder()->getBillingAddress()->getLastName(),
            'street'          => $transaction->getOrder()->getBillingAddress()->getStreet(),
            'addressaddition' => $transaction->getOrder()->getBillingAddress()->getAdditionalAddressLine1(),
            'zip'             => $transaction->getOrder()->getBillingAddress()->getZipcode(),
            'city'            => $transaction->getOrder()->getBillingAddress()->getCity(),
            'country'         => $transaction->getOrder()->getBillingAddress()->getCountry()->getIso(),
            'email'           => $transaction->getOrder()->getOrderCustomer()->getEmail(),
            'language'        => substr($language->getLocale()->getCode(), 0, 2),
            'gender'          => $transaction->getOrder()->getOrderCustomer()->getSalutation() === 'Herr' ? 'm' : 'f',
            'ip'              => $this->requestStack->getCurrentRequest() ? $this->requestStack->getCurrentRequest()->getClientIp() : null,
        ];

        if (null !== $orderCustomer->getCustomer()->getBirthday()) {
            $personalData['birthday'] = $orderCustomer->getCustomer()->getBirthday()->format('Ymd');
        }

        return $personalData;
    }

    private function getDefaultData(PaymentTransactionStruct $transaction): array
    {
        $config = $this->configReader->read($transaction->getOrder()->getSalesChannelId());

        return [
            'aid'         => $config->get('aid') ? $config->get('aid')->getValue() : '',
            'mid'         => $config->get('mid') ? $config->get('mid')->getValue() : '',
            'portalid'    => $config->get('portalid') ? $config->get('mid')->getValue() : '',
            'key'         => hash('md5', $config->get('key') ? $config->get('key')->getValue() : ''),
            'api_version' => '3.10',
            'mode'        => 'test',
            'encoding'    => 'UTF-8',
        ];
    }

    private function getPaypalData(PaymentTransactionStruct $transaction): array
    {
        $cancelUrl = $this->router->generate(
            'payone_payment_cancel',
            [
                'transaction' => $transaction->getTransactionId(),
            ],
            UrlGenerator::ABSOLUTE_URL
        );

        $errorUrl = $this->router->generate(
            'payone_payment_error',
            [
                'transaction' => $transaction->getTransactionId(),
            ],
            UrlGenerator::ABSOLUTE_URL
        );

        return [
            'request'      => 'authorization',
            'clearingtype' => 'wlt',
            'wallettype'   => 'PPE',
            'amount'       => (int) ($transaction->getAmount()->getTotalPrice() * 100),
            'currency'     => 'EUR',
            'reference'    => $transaction->getOrder()->getAutoIncrement(), // TODO: replace with ordernumber when available
            'successurl'   => $transaction->getReturnUrl(),
            'errorurl'     => $errorUrl,
            'backurl'      => $cancelUrl,
        ];
    }

    private function authorizePayment(PaymentTransactionStruct $transaction, Context $context)
    {
        $defaults     = $this->getDefaultData($transaction);
        $personalData = $this->getPayonePersonalData($transaction, $context);
        $parameters   = $this->getPaypalData($transaction);

        $postFields = array_filter(array_merge($defaults, $personalData, $parameters));

        $this->logger->debug('payone_payment_paypal - request', [
            'fields' => $postFields,
        ]);

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'cache-control: no-cache',
        ]);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($curl, CURLOPT_URL, 'https://api.pay1.de/post-gateway/');

        $response = curl_exec($curl);
        $info     = curl_getinfo($curl);
        $errno    = curl_errno($curl);

        // TODO: Handle error codes/status and curl errors

        if (empty($response)) {
            // TODO: handle fatal state - redirect to error action and display a message
        }

        $response = json_decode($response, true);

        if (empty($response)) {
            // TODO: handle fatal state - redirect to error action and display a message
        }

        if ($response['Status'] === 'ERROR') {
            // TODO: handle request error

            throw new UnprocessableEntityHttpException();
        }

        $this->logger->debug('payone_payment_paypal - response', [
            'response' => $response,
        ]);

        return $response;
    }

    /**
     * TODO: move data to a seperate entity instead of the order_transaction
     *
     * @param PaymentTransactionStruct $transaction
     * @param Context                  $context
     * @param $response
     */
    private function savePayoneResponseData(PaymentTransactionStruct $transaction, Context $context, $response): void
    {
        $criteria = new ReadCriteria([$transaction->getTransactionId()]);
        $criteria->addAssociation('order_transaction.order');

        /** @var OrderTransactionEntity $orderTransaction */
        $orderTransaction = $this->transactionRepository->read($criteria, $context)->first();

        $data = [
            'id'      => $transaction->getTransactionId(),
            'details' => array_merge(
                [
                    $response['TxId'] => [
                        'TxId'   => $response['TxId'],
                        'UserId' => $response['UserId'],
                        'paypal' => [
                            'token' => $response['AddPayData']['token'],
                        ],
                    ],
                ],
                (array) $orderTransaction->getDetails()
            ),
        ];

        $this->transactionRepository->update([$data], $context);
    }
}
