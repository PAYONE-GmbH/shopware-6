<?php

declare(strict_types=1);

namespace PayonePayment\Provider\WeChatPay\PaymentMethod;

use PayonePayment\PaymentMethod\AbstractPaymentMethod;
use PayonePayment\Provider\WeChatPay\PaymentHandler\StandardPaymentHandler;

class StandardPaymentMethod extends AbstractPaymentMethod
{
    final public const UUID = 'e9647d765b284cea9c4c0d68005665b7';

    final public const TECHNICAL_NAME = 'payone_wechat';

    final public const CONFIGURATION_PREFIX = 'weChatPay';

    public function __construct()
    {
        parent::__construct(
            StandardPaymentHandler::class,
            false,
            'PAYONE WeChat Pay',
            null,
            'Pay save and secured with WeChat Pay',
            [
                'de-DE' => [
                    'name'        => 'PAYONE WeChat Pay',
                    'description' => 'Zahle sicher und geschützt mit WeChat Pay',
                ],

                'en-GB' => [
                    'name'        => 'PAYONE WeChat Pay',
                    'description' => 'Pay save and secured with WeChat Pay',
                ],
            ],
            180,
        );
    }

    public static function getId(): string
    {
        return self::UUID;
    }

    public static function getTechnicalName(): string
    {
        return self::TECHNICAL_NAME;
    }

    public static function getConfigurationPrefix(): string
    {
        return self::CONFIGURATION_PREFIX;
    }
}
