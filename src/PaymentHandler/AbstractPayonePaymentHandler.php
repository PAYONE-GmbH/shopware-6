<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use LogicException;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Installer\CustomFieldInstaller;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A base class for payment handlers which implements common processing
 * steps of which child classes can make use of.
 */
abstract class AbstractPayonePaymentHandler implements PayonePaymentHandlerInterface
{
    public const PAYONE_STATE_COMPLETED = 'completed';
    public const PAYONE_STATE_PENDING   = 'pending';

    public const PAYONE_CLEARING_FNC = 'fnc';
    public const PAYONE_CLEARING_VOR = 'vor';
    public const PAYONE_CLEARING_REC = 'rec';

    public const PAYONE_FINANCING_PYV = 'PYV';
    public const PAYONE_FINANCING_PYS = 'PYS';
    public const PAYONE_FINANCING_PYD = 'PYD';

    /** @var ConfigReaderInterface */
    protected $configReader;

    /** @var EntityRepositoryInterface */
    protected $lineItemRepository;

    /** @var RequestStack */
    protected $requestStack;

    public function __construct(
        ConfigReaderInterface $configReader,
        EntityRepositoryInterface $lineItemRepository,
        RequestStack $requestStack
    ) {
        $this->configReader       = $configReader;
        $this->lineItemRepository = $lineItemRepository;
        $this->requestStack       = $requestStack;
    }

    /**
     * Returns true if a capture is generally not possible (or never in this context)
     * based on the current TX status notification. Use this method early in
     * isCapturable() to match common rules shared by all payment methods.
     *
     * @param array $transactionData Parameters of the TX status notification
     * @param array $customFields    Custom fields of the affected transaction
     *
     * @return bool True if the transaction cannot be captured
     */
    final protected static function isNeverCapturable(array $transactionData, array $customFields): bool
    {
        $authorizationType = $customFields[CustomFieldInstaller::AUTHORIZATION_TYPE] ?? null;

        // Transaction types of authorization are never capturable
        if ($authorizationType === TransactionStatusService::AUTHORIZATION_TYPE_AUTHORIZATION) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if a capture is possible because the TX status notification parameters
     * indicate that common defaults apply that all payment methods share. Use this method
     * as last return option in isCapturable() to match default rules shared by all
     * payment methods.
     *
     * @param array $transactionData Parameters of the TX status notification
     * @param array $customFields    Custom fields of the affected transaction
     *
     * @return bool True if the transaction can be captured based on matching default rules
     */
    final protected static function matchesIsCapturableDefaults(array $transactionData, array $customFields): bool
    {
        $txAction   = isset($transactionData['txaction']) ? strtolower($transactionData['txaction']) : null;
        $price      = isset($transactionData['price']) ? ((float) $transactionData['price']) : null;
        $receivable = isset($transactionData['receivable']) ? ((float) $transactionData['receivable']) : null;

        // Allow further captures for TX status that indicates a partial capture
        if (
            $txAction === TransactionStatusService::ACTION_CAPTURE &&
            is_float($price) && is_float($receivable) &&
            $receivable > 0.0 && $receivable < $price
        ) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if a refund / debit is generally not possible (or never in this context)
     * based on the current TX status notification. Use this method early in
     * isRefundable() to match common rules shared by all payment methods.
     *
     * @param array $transactionData Parameters of the TX status notification
     * @param array $customFields    Custom fields of the affected transaction
     *
     * @return bool True if the transaction cannot be captured
     */
    final protected static function isNeverRefundable(array $transactionData, array $customFields): bool
    {
        return false;
    }

    /**
     * Returns true if a refund is possible because the TX status notification parameters
     * indicate that common defaults apply that all payment methods share. Use this method
     * as last return option in isRefundable() to match default rules shared by all
     * payment methods.
     *
     * @param array $transactionData Parameters of the TX status notification
     * @param array $customFields    Custom fields of the affected transaction
     *
     * @return bool True if the transaction can be refunded based on matching default rules
     */
    final protected static function matchesIsRefundableDefaults(array $transactionData, array $customFields): bool
    {
        $txAction   = isset($transactionData['txaction']) ? strtolower($transactionData['txaction']) : null;
        $receivable = isset($transactionData['receivable']) ? ((float) $transactionData['receivable']) : null;

        // Allow refund if capture TX status and receivable indicate we have outstanding funds
        if ($txAction === TransactionStatusService::ACTION_CAPTURE && $receivable > 0.0) {
            return true;
        }

        // If an incoming debit TX status indicates a partial refund we allow further refunds
        if ($txAction === TransactionStatusService::ACTION_DEBIT && $receivable > 0.0) {
            return true;
        }

        // We got paid and that means we can refund
        if ($txAction === TransactionStatusService::ACTION_PAID) {
            return true;
        }

        return false;
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

    protected function fetchRequestData(): RequestDataBag
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new LogicException('missing current request');
        }

        return new RequestDataBag($request->request->all());
    }
}
