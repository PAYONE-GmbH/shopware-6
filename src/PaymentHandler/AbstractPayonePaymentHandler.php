<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Installer\CustomFieldInstaller;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A base class for payment handlers which implements common processing
 * steps of which child classes can make use of.
 */
abstract class AbstractPayonePaymentHandler implements PayonePaymentHandlerInterface
{
    final public const PAYONE_STATE_COMPLETED = 'completed';
    final public const PAYONE_STATE_PENDING = 'pending';

    final public const PAYONE_CLEARING_FNC = 'fnc';
    final public const PAYONE_CLEARING_VOR = 'vor';
    final public const PAYONE_CLEARING_REC = 'rec';

    final public const PAYONE_FINANCING_PIV = 'PIV';
    final public const PAYONE_FINANCING_PIN = 'PIN';
    final public const PAYONE_FINANCING_PDD = 'PDD';

    final public const PAYONE_FINANCING_PYV = 'PYV';
    final public const PAYONE_FINANCING_PYS = 'PYS';
    final public const PAYONE_FINANCING_PYD = 'PYD';

    final public const PAYONE_FINANCING_RPV = 'RPV';
    final public const PAYONE_FINANCING_RPS = 'RPS';
    final public const PAYONE_FINANCING_RPD = 'RPD';

    public function __construct(
        protected ConfigReaderInterface $configReader,
        protected EntityRepository $lineItemRepository,
        protected RequestStack $requestStack
    ) {
    }

    public function getValidationDefinitions(SalesChannelContext $salesChannelContext): array
    {
        return [];
    }

    /**
     * Returns true if a capture is generally not possible (or never in this context)
     * based on the current TX status notification. Use this method early in
     * isCapturable() to match common rules shared by all payment methods.
     *
     * @param array $payoneTransactionData Updated transaction data
     *
     * @return bool True if the transaction cannot be captured
     */
    final protected static function isNeverCapturable(array $payoneTransactionData): bool
    {
        $authorizationType = $payoneTransactionData['authorizationType'] ?? null;

        // Transaction types of authorization are never capturable
        return $authorizationType === TransactionStatusService::AUTHORIZATION_TYPE_AUTHORIZATION;
    }

    /**
     * Returns true if a capture is possible because the TX status notification parameters
     * indicate that common defaults apply that all payment methods share. Use this method
     * as last return option in isCapturable() to match default rules shared by all
     * payment methods.
     *
     * @param array $transactionData Parameters of the TX status notification
     *
     * @return bool True if the transaction can be captured based on matching default rules
     */
    final protected static function matchesIsCapturableDefaults(array $transactionData): bool
    {
        $txAction = isset($transactionData['txaction']) ? strtolower((string) $transactionData['txaction']) : null;
        $price = isset($transactionData['price']) ? ((float) $transactionData['price']) : null;
        $receivable = isset($transactionData['receivable']) ? ((float) $transactionData['receivable']) : null;

        // Allow further captures for TX status that indicates a partial capture
        return $txAction === TransactionStatusService::ACTION_CAPTURE
            && \is_float($price) && \is_float($receivable)
            && $receivable > 0.0 && $receivable < $price;
    }

    /**
     * Helper function to check if the transaction is appointed and completed.
     * Used in various payment handlers to check if the transaction is captureable.
     *
     * @param array $transactionData Parameters of the TX status notification
     *
     * @return bool True if the transaction is appointed and completed
     */
    final protected static function isTransactionAppointedAndCompleted(array $transactionData): bool
    {
        $txAction = isset($transactionData['txaction']) ? strtolower((string) $transactionData['txaction']) : null;
        $transactionStatus = isset($transactionData['transaction_status']) ? strtolower((string) $transactionData['transaction_status']) : null;

        return $txAction === TransactionStatusService::ACTION_APPOINTED && $transactionStatus === TransactionStatusService::STATUS_COMPLETED;
    }

    /**
     * Returns true if a refund / debit is generally not possible (or never in this context)
     * based on the current TX status notification. Use this method early in
     * isRefundable() to match common rules shared by all payment methods.
     *
     * @param array $transactionData Parameters of the TX status notification
     *
     * @return bool True if the transaction cannot be captured
     */
    final protected static function isNeverRefundable(array $transactionData): bool
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
     *
     * @return bool True if the transaction can be refunded based on matching default rules
     */
    final protected static function matchesIsRefundableDefaults(array $transactionData): bool
    {
        $txAction = isset($transactionData['txaction']) ? strtolower((string) $transactionData['txaction']) : null;
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
        return $this->configReader->read($salesChannelId)->getString($configKey, $default);
    }

    protected function preparePayoneOrderTransactionData(array $request, array $response, array $fields = []): array
    {
        $key = (new \DateTime())->format(\DATE_ATOM);

        return array_merge([
            'authorizationType' => $request['request'],
            'lastRequest' => $request['request'],
            'transactionId' => (string) $response['txid'],
            'sequenceNumber' => -1,
            'userId' => $response['userid'],
            'transactionState' => $response['status'],
            'transactionData' => [
                $key => ['request' => $request, 'response' => $response],
            ],
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
                'id' => $lineItemEntity->getId(),
                'customFields' => array_merge($lineItemEntity->getCustomFields() ?? [], $customFields),
            ];
        }

        $this->lineItemRepository->update($saveData, $context);
    }

    protected function getMinimumDate(): \DateTimeInterface
    {
        return (new \DateTime())->modify('-18 years')->setTime(0, 0);
    }

    protected function customerHasCompanyAddress(SalesChannelContext $salesChannelContext): bool
    {
        $customer = $salesChannelContext->getCustomer();

        if ($customer === null) {
            return false;
        }

        $billingAddress = $customer->getActiveBillingAddress();

        if ($billingAddress === null) {
            return false;
        }

        return !empty($billingAddress->getCompany());
    }

    final protected function getConfigKeyPrefix(): string
    {
        return ConfigurationPrefixes::CONFIGURATION_PREFIXES[static::class];
    }

    /**
     * Define this method to return either AbstractRequestParameterBuilder::REQUEST_ACTION_AUTHORIZE
     * or AbstractRequestParameterBuilder::REQUEST_ACTION_PREAUTHORIZE that should be used as default authorization
     * method if it is not configured by shop owner.
     */
    abstract protected function getDefaultAuthorizationMethod(): string;

    /**
     * Override this method to validate the posted data. Throw a PayoneRequestException if validation fails!
     */
    protected function validateRequestData(RequestDataBag $dataBag): void
    {
    }

    /**
     * Override this method to define additional transaction data that should be saved to the transaction
     * data extension
     */
    protected function getAdditionalTransactionData(RequestDataBag $dataBag, array $request, array $response): array
    {
        return [];
    }

    abstract protected function createPaymentException(string $orderTransactionId, string $errorMessage, ?\Throwable $e): \Throwable;
}
