<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use LogicException;
use PayonePayment\Components\CartHasher\CartHasherInterface;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Components\TransactionStatus\TransactionStatusService;
use PayonePayment\Installer\CustomFieldInstaller;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\SyncPaymentProcessException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    public const PAYONE_FINANCING_RPV = 'RPV';
    public const PAYONE_FINANCING_RPS = 'RPS';
    public const PAYONE_FINANCING_RPD = 'RPD';

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

    public function getValidationDefinitions(SalesChannelContext $salesChannelContext): array
    {
        return [];
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
     * Helper function to check if the transaction is appointed and completed.
     * Used in various payment handlers to check if the transaction is captureable.
     *
     * @param array $transactionData Parameters of the TX status notification
     *
     * @return bool True if the transaction is appointed and completed
     */
    final protected static function isTransactionAppointedAndCompleted(array $transactionData): bool
    {
        $txAction          = isset($transactionData['txaction']) ? strtolower($transactionData['txaction']) : null;
        $transactionStatus = isset($transactionData['transaction_status']) ? strtolower($transactionData['transaction_status']) : null;

        return $txAction === TransactionStatusService::ACTION_APPOINTED && $transactionStatus === TransactionStatusService::STATUS_COMPLETED;
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

        return $configuration->getString($configKey, $default);
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

    protected function getMinimumDate(): \DateTimeInterface
    {
        return (new \DateTime())->modify('-18 years')->setTime(0, 0);
    }

    protected function customerHasCompanyAddress(SalesChannelContext $salesChannelContext): bool
    {
        $customer = $salesChannelContext->getCustomer();

        if (null === $customer) {
            return false;
        }

        $billingAddress = $customer->getActiveBillingAddress();

        if (null === $billingAddress) {
            return false;
        }

        return !empty($billingAddress->getCompany());
    }

    /**
     * @param RequestDataBag $requestDataBag
     * @param SyncPaymentTransactionStruct|AsyncPaymentTransactionStruct $paymentTransaction
     * @param SalesChannelContext $salesChannelContext
     */
    public function validateCartHash(
        RequestDataBag $requestDataBag,
        $paymentTransaction,
        SalesChannelContext $salesChannelContext,
        string $exceptionClass = null
    ): void
    {
        $missingPropertyMessage = 'please create protected property `%s` of type %s on class ' . get_class($this);
        if (!property_exists($this, 'cartHasher')) {
            throw new \RuntimeException(sprintf($missingPropertyMessage, 'cartHasher', CartHasherInterface::class));
        }

        if (!property_exists($this, 'translator')) {
            throw new \RuntimeException(sprintf($missingPropertyMessage, 'translator', TranslatorInterface::class));
        }

        $cartHash = (string)$requestDataBag->get('carthash');
        $transactionEntity = $paymentTransaction->getOrderTransaction();

        if (!$this->cartHasher->validate($paymentTransaction->getOrder(), $cartHash, $salesChannelContext)) {
            if (!$exceptionClass) {
                if ($this instanceof SynchronousPaymentHandlerInterface) {
                    $exceptionClass = SyncPaymentProcessException::class;
                } else if ($this instanceof AsynchronousPaymentHandlerInterface) {
                    $exceptionClass = AsyncPaymentProcessException::class;
                }
            }
            throw new $exceptionClass(
                $transactionEntity->getId(),
                $this->translator->trans('PayonePayment.errorMessages.genericError')
            );
        }
    }
}
