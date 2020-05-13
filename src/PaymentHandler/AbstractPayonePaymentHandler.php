<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\DataHandler\LineItem\LineItemDataHandlerInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use Shopware\Core\Framework\Context;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;

/**
 * A base class for payment handlers which implements common processing
 * steps of which child classes can make use of.
 */
abstract class AbstractPayonePaymentHandler implements PayonePaymentHandlerInterface
{
    /** @var ConfigReaderInterface */
    protected $configReader;

    /** @var LineItemDataHandlerInterface */
    protected $lineItemDataHandler;

    public function __construct(
        ConfigReaderInterface $configReader,
        LineItemDataHandlerInterface $lineItemDataHandler
    ) {
        $this->configReader = $configReader;
        $this->lineItemDataHandler = $lineItemDataHandler;
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
        $configuration = $this->configReader->read($salesChannelId);
        return $configuration->get($configKey, $default);
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

    protected function setLineItemCustomFields(OrderLineItemCollection $lineItem, Context $context, array $fields = []): void
    {
        $customFields =  array_merge([
            CustomFieldInstaller::CAPTURED_QUANTITY => 0,
            CustomFieldInstaller::REFUNDED_QUANTITY => 0
        ], $fields);

        foreach ($lineItem->getElements() as $lineItemEntity) {
            $customFields = array_merge($lineItemEntity->getCustomFields() ?? [], $customFields);

            $this->lineItemDataHandler->saveLineItemData($lineItemEntity, $context, $customFields);
        }
    }
}
