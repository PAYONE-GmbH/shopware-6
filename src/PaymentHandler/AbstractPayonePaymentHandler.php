<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Installer\CustomFieldInstaller;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

/**
 * A base class for payment handlers which implements common processing
 * steps of which child classes can make use of.
 */
abstract class AbstractPayonePaymentHandler implements PayonePaymentHandlerInterface
{
    public const PAYONE_STATE_COMPLETED = 'completed';
    public const PAYONE_STATE_PENDING   = 'pending';

    public const PAYONE_CLEARING_FNC = 'fnc';

    public const PAYONE_FINANCING_PYV = 'PYV';
    public const PAYONE_FINANCING_PYS = 'PYS';
    public const PAYONE_FINANCING_PYD = 'PYD';

    /** @var ConfigReaderInterface */
    protected $configReader;

    /** @var EntityRepositoryInterface */
    protected $lineItemRepository;

    public function __construct(
        ConfigReaderInterface $configReader,
        EntityRepositoryInterface $lineItemRepository
    ) {
        $this->configReader       = $configReader;
        $this->lineItemRepository = $lineItemRepository;
    }

    /**
     * Returns the configured authorization method for this payment method.
     *
     * @param string $salesChannelId the ID of the associated sales channel
     * @param string $configKey      the config key of the configured authorization method
     * @param string $default        a default authorization method if no proper configuration can be found
     *
     * @return string the authorization method to use for this payment process
     */
    protected function getAuthorizationMethod(string $salesChannelId, string $configKey, string $default): string
    {
        $configuration = $this->configReader->read($salesChannelId);

        return $configuration->get($configKey, $default);
    }

    /**
     * Prepares and returns custom fields for the transaction.
     *
     * @param array $request  the PAYONE request parameters
     * @param array $response the PAYONE response parameters
     * @param array $fields   any additional custom fields (higher priority)
     *
     * @return array a resulting array of custom fields for the transaction
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
        $customFields = array_merge([
            CustomFieldInstaller::CAPTURED_QUANTITY => 0,
            CustomFieldInstaller::REFUNDED_QUANTITY => 0,
        ], $fields);

        $saveData = [];

        foreach ($lineItem->getElements() as $lineItemEntity) {
            $saveData[] = [
                'id'           => $lineItemEntity->getId(),
                'customFields' => array_merge($lineItemEntity->getCustomFields() ?? [], $customFields),
            ];
        }

        $this->lineItemRepository->update($saveData, $context);
    }

    protected function getBaseCustomFields(string $status): array
    {
        return [
            CustomFieldInstaller::TRANSACTION_STATE => $status,
            CustomFieldInstaller::ALLOW_CAPTURE     => false,
            CustomFieldInstaller::CAPTURED_AMOUNT   => 0,
            CustomFieldInstaller::ALLOW_REFUND      => false,
            CustomFieldInstaller::REFUNDED_AMOUNT   => 0,
        ];
    }
}
