<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * A base class for payment handlers which implements common processing
 * steps of which child classes can make use of.
 */
abstract class AbstractPayonePaymentHandler implements PayonePaymentHandlerInterface
{
    /** @var ConfigReaderInterface */
    protected $configReader;

    /** @var PayoneClientInterface */
    protected $client;

    /** @var TranslatorInterface */
    protected $translator;

    public function __construct(
        ConfigReaderInterface $configReader,
        PayoneClientInterface $client,
        TranslatorInterface $translator
    ) {
        $this->configReader = $configReader;
        $this->client       = $client;
        $this->translator   = $translator;
    }

    /**
     * Returns the configured authorization method for this payment method.
     *
     * @param string $salesChannelId The ID of the associated sales channel.
     * @param string $configKey The config key of the configured authorization method.
     * @param string $default A default authorization method if no proper configuration can be found.
     * @return string The authorization method to use for this payment process.
     */
    protected function getAuthorizationMethod(string $salesChannelId, string $configKey, string $default): string
    {
        $configuration              = $this->configReader->read($salesChannelId);
        $defaultAuthorizationMethod = $configuration->get('authorizationMethod', $default);
        $authorizationMethod        = $configuration->get($configKey, $defaultAuthorizationMethod);

        return $authorizationMethod === 'default'
            ? $defaultAuthorizationMethod
            : $authorizationMethod;
    }

    /**
     * Sends the provided request parameters to PAYONE and returns
     * the received response parameters.
     *
     * @param array $request The request parameters to send.
     * @param SyncPaymentTransactionStruct $transaction The related transaction struct.
     * @return array The response parameters.
     * @throws AsyncPaymentProcessException If the payment fails for any reason.
     */
    protected function sendRequest(array $request, SyncPaymentTransactionStruct $transaction): array
    {
        try {
            return $this->client->request($request);
        } catch (PayoneRequestException $exception) {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $exception->getResponse()['error']['CustomerMessage']
            );
        } catch (Throwable $exception) {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }
    }

    /**
     * Prepares and returns custom fields for the transaction.
     *
     * @param array $request The PAYONE request parameters.
     * @param array $response The PAYONE response parameters.
     * @param array $fields Any additional custom fields (higher priority).
     * @return array A resulting array of custom fields for the transaction.
     */
    protected function prepareTransactionCustomFields(array $request, array $response, array $fields = []): array
    {
        return array_merge([
            CustomFieldInstaller::AUTHORIZATION_TYPE => $request['request'],
            CustomFieldInstaller::LAST_REQUEST       => $request['request'],
            CustomFieldInstaller::TRANSACTION_ID     => (string) $response['txid'],
            CustomFieldInstaller::SEQUENCE_NUMBER    => -1,
            CustomFieldInstaller::USER_ID            => $response['userid'],
        ], $fields);
    }
}
