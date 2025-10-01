<?php

declare(strict_types=1);

namespace PayonePayment\Provider\AmazonPay\RequestParameter\Enricher\Standard;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\PaymentHandler\PaymentHandlerInterface;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\Provider\AmazonPay\Enum\AmazonPayMetaEnum;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\Enricher\ApplyPhoneParameterTrait;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class AuthorizeRequestParameterEnricher implements RequestParameterEnricherInterface
{
    use ApplyPhoneParameterTrait;

    public function __construct(
        private ConfigReaderInterface $configReader,
        EntityRepository $orderAddressRepository,
    ) {
        $this->orderAddressRepository = $orderAddressRepository;
    }

    public function enrich(AbstractRequestDto $arguments): array
    {
        if ($arguments->action !== RequestActionEnum::AUTHORIZE->value) {
            return [];
        }

        $storeName  = $this->getStoreName($arguments->salesChannelContext, $arguments->paymentHandler);
        $parameters = [
            'request'                   => $arguments->action,
            'clearingtype'              => PayoneClearingEnum::WALLET->value,
            'wallettype'                => AmazonPayMetaEnum::WALLET_TYPE->value,
            'add_paydata[platform_id]'  => AmazonPayMetaEnum::PLATFORM_ID->value,
            'add_paydata[storename]'    => $storeName,
            'add_paydata[checkoutMode]' => 'ProcessOrder',
            'add_paydata[productType]'  => 'PayAndShip',
        ];

        $this->applyPhoneParameter(
            $arguments->paymentTransaction->order,
            $parameters,
            $arguments->requestData,
            $arguments->salesChannelContext->getContext(),
        );

        return $parameters;
    }

    private function getStoreName(
        SalesChannelContext $context,
        PaymentHandlerInterface $paymentHandlerClass,
    ): string|null {
        $value = $this->configReader->read($context->getSalesChannelId())
            ->getString($paymentHandlerClass->getConfigKeyPrefix())
        ;

        return !empty($value) ? $value : null;
    }
}
