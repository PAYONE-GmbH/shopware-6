<?php

declare(strict_types=1);

namespace PayonePayment\Installer;

use PayonePayment\Payone\Request\RequestActionEnum;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;

readonly class SettingsDefaults
{
    final public const DEFAULT_VALUES = [
        'transactionMode'                          => 'test',

        // Default authorization modes for Payone payment methods
        'creditCardAuthorizationMethod'            => RequestActionEnum::PREAUTHORIZE->value,
        'openInvoiceAuthorizationMethod'           => RequestActionEnum::PREAUTHORIZE->value,
        'debitAuthorizationMethod'                 => RequestActionEnum::AUTHORIZE->value,
        'securedDirectDebitAuthorizationMethod'    => RequestActionEnum::PREAUTHORIZE->value,
        'securedInstallmentAuthorizationMethod'    => RequestActionEnum::PREAUTHORIZE->value,
        'securedInvoiceAuthorizationMethod'        => RequestActionEnum::PREAUTHORIZE->value,
        'secureInvoiceAuthorizationMethod'         => RequestActionEnum::PREAUTHORIZE->value,
        // missing in settings - so no default value required:
        //'prepaymentAuthorizationMethod'       => RequestActionEnum::PREAUTHORIZE->value,

        // Default authorization modes for other payment methods
        'alipayAuthorizationMethod'                => RequestActionEnum::AUTHORIZE->value,
        'amazonPayAuthorizationMethod'             => RequestActionEnum::AUTHORIZE->value,
        'amazonPayExpressAuthorizationMethod'      => RequestActionEnum::AUTHORIZE->value,
        'applePayAuthorizationMethod'              => RequestActionEnum::PREAUTHORIZE->value,
        'bancontactAuthorizationMethod'            => RequestActionEnum::AUTHORIZE->value,
        'epsAuthorizationMethod'                   => RequestActionEnum::AUTHORIZE->value,
        'googlePayAuthorizationMethod'             => RequestActionEnum::PREAUTHORIZE->value,
        'iDealAuthorizationMethod'                 => RequestActionEnum::AUTHORIZE->value,
        'klarnaDirectDebitAuthorizationMethod'     => RequestActionEnum::PREAUTHORIZE->value,
        'klarnaInstallmentAuthorizationMethod'     => RequestActionEnum::PREAUTHORIZE->value,
        'klarnaInvoiceAuthorizationMethod'         => RequestActionEnum::PREAUTHORIZE->value,
        'paydirektAuthorizationMethod'             => RequestActionEnum::AUTHORIZE->value,
        'payolutionDebitAuthorizationMethod'       => RequestActionEnum::PREAUTHORIZE->value,
        'payolutionInstallmentAuthorizationMethod' => RequestActionEnum::AUTHORIZE->value,
        'payolutionInvoicingAuthorizationMethod'   => RequestActionEnum::PREAUTHORIZE->value,
        'paypalAuthorizationMethod'                => RequestActionEnum::PREAUTHORIZE->value,
        'paypalExpressAuthorizationMethod'         => RequestActionEnum::PREAUTHORIZE->value,
        'paypalV2AuthorizationMethod'              => RequestActionEnum::PREAUTHORIZE->value,
        'paypalV2ExpressAuthorizationMethod'       => RequestActionEnum::PREAUTHORIZE->value,
        'postfinanceCardAuthorizationMethod'       => RequestActionEnum::PREAUTHORIZE->value,
        'postfinanceWalletAuthorizationMethod'     => RequestActionEnum::PREAUTHORIZE->value,
        'przelewy24AuthorizationMethod'            => RequestActionEnum::AUTHORIZE->value,
        'ratepayInvoiceAuthorizationMethod'        => RequestActionEnum::PREAUTHORIZE->value,
        'ratepayDebitAuthorizationMethod'          => RequestActionEnum::PREAUTHORIZE->value,
        'ratepayInstallmentAuthorizationMethod'    => RequestActionEnum::PREAUTHORIZE->value,
        'sofortAuthorizationMethod'                => RequestActionEnum::AUTHORIZE->value,
        'trustlyAuthorizationMethod'               => RequestActionEnum::PREAUTHORIZE->value,
        'weChatPayAuthorizationMethod'             => RequestActionEnum::AUTHORIZE->value,
        'weroAuthorizationMethod'                  => RequestActionEnum::PREAUTHORIZE->value,

        // Click to Pay – VISA defaults
        'clickToPayVisaSrcInitiatorId'              => '2662KBGOLX92KS4XIFYU213JLdGTvLhYkOB-_1gLo1D1jOqgM',
        'clickToPayVisaEncryptionKey'               => 'GQJIKLOAMZWIT8IRIGHR14vQUlllxiMWf-XSHQHvjI5wuTZ2w',
        'clickToPayVisaNModulus'                    => 'kPujwVJjevI_oeZwZoA2Wjt94DFcMvRCab8iRiEGrGfKWtNCwQYkylyuRoB615cYm2BVbvoKH8Yyv0aC3dwah6UmOdJszmL0pV_cbx_tXzWgYg3sYNsp0sBxUFcQ1A6DVbyOxxJbmnwlHGE5fkuzJr-qqul3RswsCG-vPrh_--2_RSipa9lVr9gvfI4AbFABLTqKeto0rWPbIBKdhcGQ7JMPxzq8239KPUZfSyNueAcdL-yHADi3L2VSzdF7tS7si3ue_IFoXDpbggsFxvEt79UlBDOBsagc_ms9_ZsYlJaKCT8ZjwhakMo_-Zdc97mudVj1jz2_L5l4l_zibF5riw',

        // Click to Pay – Mastercard defaults
        'clickToPayMastercardSrcInitiatorId'        => '559003b0-5d17-4d89-aa2b-b02a4023d64d',

        // Click to Pay – UI defaults
        'clickToPayButtonStyle'                     => 'OUTLINED',
        'clickToPayButtonTextCase'                  => 'capitalize',
        'clickToPayButtonAndBadgeColor'             => '#3B82F6',
        'clickToPayButtonFilledHoverColor'          => '#6390f2',
        'clickToPayButtonOutlinedHoverColor'        => '#60A5FA',
        'clickToPayButtonDisabledColor'             => '#A5B4FC',
        'clickToPayCardItemActiveColor'             => '#6390f2',
        'clickToPayButtonAndBadgeTextColor'         => '#FFFFFF',
        'clickToPayLinkTextColor'                   => '#3B82F6',
        'clickToPayAccentColor'                     => '#6390f2',
        'clickToPayFontFamily'                      => 'Sansation',
        'clickToPayButtonAndInputRadius'            => '1rem',
        'clickToPayCardItemRadius'                  => '2rem',

        // Other default settings
        'ratepayInvoiceDeviceFingerprintSnippetId'         => 'ratepay',
        'securedInvoiceAllowDifferentShippingAddress'      => false,
        'securedInstallmentAllowDifferentShippingAddress'  => false,
        'securedDirectDebitAllowDifferentShippingAddress'  => false,
        'amazonPayExpressRestrictPOBoxes'                  => true,
        'amazonPayExpressRestrictPackstations'             => true,

        // Default payment status mapping
        'paymentStatusAppointed'                   => StateMachineTransitionActions::ACTION_REOPEN,
        'paymentStatusCapture'                     => StateMachineTransitionActions::ACTION_PAID,
        'paymentStatusPartialCapture'              => StateMachineTransitionActions::ACTION_PAID_PARTIALLY,
        'paymentStatusPaid'                        => StateMachineTransitionActions::ACTION_PAID,
        'paymentStatusUnderpaid'                   => StateMachineTransitionActions::ACTION_PAID_PARTIALLY,
        'paymentStatusCancelation'                 => StateMachineTransitionActions::ACTION_CANCEL,
        'paymentStatusRefund'                      => StateMachineTransitionActions::ACTION_REFUND,
        'paymentStatusPartialRefund'               => StateMachineTransitionActions::ACTION_REFUND_PARTIALLY,
        'paymentStatusDebit'                       => StateMachineTransitionActions::ACTION_PAID,
        'paymentStatusReminder'                    => StateMachineTransitionActions::ACTION_REMIND,
        'paymentStatusVauthorization'              => '',
        'paymentStatusVsettlement'                 => '',
        'paymentStatusTransfer'                    => StateMachineTransitionActions::ACTION_CANCEL,
        'paymentStatusInvoice'                     => StateMachineTransitionActions::ACTION_PAID,
        'paymentStatusFailed'                      => StateMachineTransitionActions::ACTION_CANCEL,
    ];

    final public const UPDATE_VALUES = [ // Updated for 6.2
        'paymentStatusCapture'        => [ 'pay' => StateMachineTransitionActions::ACTION_PAID ],
        'paymentStatusPartialCapture' => [ 'pay_partially' => StateMachineTransitionActions::ACTION_PAID_PARTIALLY ],
        'paymentStatusPaid'           => [ 'pay' => StateMachineTransitionActions::ACTION_PAID ],
        'paymentStatusUnderpaid'      => [ 'pay_partially' => StateMachineTransitionActions::ACTION_PAID_PARTIALLY ],
        'paymentStatusDebit'          => [ 'pay' => StateMachineTransitionActions::ACTION_PAID ],
        'paymentStatusInvoice'        => [ 'pay' => StateMachineTransitionActions::ACTION_PAID ],
    ];
}