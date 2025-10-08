<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Eps\PaymentHandler;

use PayonePayment\Components\TransactionStatus\Enum\TransactionActionEnum;
use PayonePayment\PaymentHandler\AbstractPaymentHandler;
use PayonePayment\PaymentHandler\BasicRedirectResponseTrait;
use PayonePayment\PaymentHandler\BasicValidationDefinitionTrait;
use PayonePayment\PaymentHandler\FinalizeTrait;
use PayonePayment\PaymentHandler\IsCapturableTrait;
use PayonePayment\PaymentHandler\IsRefundableTrait;
use PayonePayment\PaymentHandler\PaymentHandlerPayExecutorInterface;
use PayonePayment\PaymentHandler\RequestEnricherChainTrait;
use PayonePayment\PaymentHandler\ResponseHandlerTrait;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Provider\Eps\PaymentMethod\StandardPaymentMethod;
use PayonePayment\Provider\Eps\ResponseHandler\StandardResponseHandler;
use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use PayonePayment\Service\PaymentStateHandlerService;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class StandardPaymentHandler extends AbstractPaymentHandler
{
    use BasicRedirectResponseTrait;
    use BasicValidationDefinitionTrait;
    use FinalizeTrait;
    use IsCapturableTrait;
    use IsRefundableTrait;
    use RequestEnricherChainTrait;
    use ResponseHandlerTrait;

    /**
     * Valid iDEAL bank groups according to:
     * https://docs.payone.com/pages/releaseview.action?pageId=1213908
     */
    protected const VALID_EPS_BANK_GROUPS = [
        'ARZ_BAF',
        'ARZ_BCS',
        'ARZ_HAA',
        'ARZ_HTB',
        'ARZ_OAB',
        'ARZ_OVB',
        'ARZ_VLH',
        'BA_AUS',
        'BAWAG_ESY',
        'BAWAG_PSK',
        'EPS_AAB',
        'EPS_BKB',
        'EPS_BKS',
        'EPS_CBGG',
        'EPS_DB',
        'EPS_HBL',
        'EPS_OBAG',
        'EPS_MFB',
        'EPS_NOELB',
        'EPS_SCHEL',
        'EPS_SCHOELLER',
        'EPS_SPDBA',
        'EPS_SPDBW',
        'EPS_VKB',
        'EPS_VLB',
        'EPS_VRBB',
        'HRAC_OOS',
        'RAC_RAC',
        'SPARDAT_EBS',
    ];

    public function __construct(
        protected readonly PaymentHandlerPayExecutorInterface $payExecutor,
        StandardResponseHandler $responseHandler,
        PaymentStateHandlerService $stateHandler,
        RequestParameterEnricherChain $requestEnricherChain,
    ) {
        $this->responseHandler      = $responseHandler;
        $this->requestEnricherChain = $requestEnricherChain;
        $this->stateHandler         = $stateHandler;
    }

    #[\Override]
    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        return PaymentHandlerType::REFUND === $type;
    }

    #[\Override]
    public function getConfigKeyPrefix(): string
    {
        return StandardPaymentMethod::getConfigurationPrefix();
    }

    #[\Override]
    public function getDefaultAuthorizationMethod(): string
    {
        return RequestActionEnum::AUTHORIZE->value;
    }

    #[\Override]
    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool
    {
        if (self::isNeverCapturable($payoneTransActionData)) {
            return false;
        }

        $txAction = isset($transactionData['txaction']) ? \strtolower((string) $transactionData['txaction']) : null;

        if (TransactionActionEnum::PAID->value === $txAction) {
            return true;
        }

        return self::matchesIsCapturableDefaults($transactionData);
    }

    /**
     * @throws PayoneRequestException
     */
    #[\Override]
    public function validateRequestData(RequestDataBag $dataBag): void
    {
        $bankGroup = $dataBag->get('epsBankGroup');

        if (!\in_array($bankGroup, static::VALID_EPS_BANK_GROUPS, true)) {
            throw new PayoneRequestException('No valid EPS bank group');
        }
    }

    #[\Override]
    public function getPaymentMethodUuid(): string
    {
        return StandardPaymentMethod::getId();
    }
}
