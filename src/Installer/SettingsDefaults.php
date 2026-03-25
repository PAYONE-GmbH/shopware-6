<?php

declare(strict_types=1);

namespace PayonePayment\Installer;

use PayonePayment\Payone\Request\RequestActionEnum;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;

readonly class SettingsDefaults
{
    final public const DEFAULT_VALUES = [
        'transactionMode'                                 => 'test',

        // Default authorization modes for Payone payment methods
        'creditCardAuthorizationMethod'                   => RequestActionEnum::PREAUTHORIZE->value,
        'openInvoiceAuthorizationMethod'                  => RequestActionEnum::PREAUTHORIZE->value,
        'debitAuthorizationMethod'                        => RequestActionEnum::AUTHORIZE->value,
        'securedDirectDebitAuthorizationMethod'           => RequestActionEnum::PREAUTHORIZE->value,
        'securedInstallmentAuthorizationMethod'           => RequestActionEnum::PREAUTHORIZE->value,
        'securedInvoiceAuthorizationMethod'               => RequestActionEnum::PREAUTHORIZE->value,
        'secureInvoiceAuthorizationMethod'                => RequestActionEnum::PREAUTHORIZE->value,
        // missing in settings - so no default value required:
        //'prepaymentAuthorizationMethod'       => RequestActionEnum::PREAUTHORIZE->value,

        // Default authorization modes for other payment methods
        'alipayAuthorizationMethod'                       => RequestActionEnum::AUTHORIZE->value,
        'amazonPayAuthorizationMethod'                    => RequestActionEnum::AUTHORIZE->value,
        'amazonPayExpressAuthorizationMethod'             => RequestActionEnum::AUTHORIZE->value,
        'applePayAuthorizationMethod'                     => RequestActionEnum::PREAUTHORIZE->value,
        'bancontactAuthorizationMethod'                   => RequestActionEnum::AUTHORIZE->value,
        'epsAuthorizationMethod'                          => RequestActionEnum::AUTHORIZE->value,
        'googlePayAuthorizationMethod'                    => RequestActionEnum::PREAUTHORIZE->value,
        'iDealAuthorizationMethod'                        => RequestActionEnum::AUTHORIZE->value,
        'klarnaDirectDebitAuthorizationMethod'            => RequestActionEnum::PREAUTHORIZE->value,
        'klarnaInstallmentAuthorizationMethod'            => RequestActionEnum::PREAUTHORIZE->value,
        'klarnaInvoiceAuthorizationMethod'                => RequestActionEnum::PREAUTHORIZE->value,
        'paydirektAuthorizationMethod'                    => RequestActionEnum::AUTHORIZE->value,
        'payolutionDebitAuthorizationMethod'              => RequestActionEnum::PREAUTHORIZE->value,
        'payolutionInstallmentAuthorizationMethod'        => RequestActionEnum::AUTHORIZE->value,
        'payolutionInvoicingAuthorizationMethod'          => RequestActionEnum::PREAUTHORIZE->value,
        'paypalAuthorizationMethod'                       => RequestActionEnum::PREAUTHORIZE->value,
        'paypalExpressAuthorizationMethod'                => RequestActionEnum::PREAUTHORIZE->value,
        'paypalV2AuthorizationMethod'                     => RequestActionEnum::PREAUTHORIZE->value,
        'paypalV2ExpressAuthorizationMethod'              => RequestActionEnum::PREAUTHORIZE->value,
        'postfinanceCardAuthorizationMethod'              => RequestActionEnum::PREAUTHORIZE->value,
        'postfinanceWalletAuthorizationMethod'            => RequestActionEnum::PREAUTHORIZE->value,
        'przelewy24AuthorizationMethod'                   => RequestActionEnum::AUTHORIZE->value,
        'ratepayInvoiceAuthorizationMethod'               => RequestActionEnum::PREAUTHORIZE->value,
        'ratepayDebitAuthorizationMethod'                 => RequestActionEnum::PREAUTHORIZE->value,
        'ratepayInstallmentAuthorizationMethod'           => RequestActionEnum::PREAUTHORIZE->value,
        'sofortAuthorizationMethod'                       => RequestActionEnum::AUTHORIZE->value,
        'trustlyAuthorizationMethod'                      => RequestActionEnum::PREAUTHORIZE->value,
        'weChatPayAuthorizationMethod'                    => RequestActionEnum::AUTHORIZE->value,
        'weroAuthorizationMethod'                         => RequestActionEnum::PREAUTHORIZE->value,

        // Click to Pay – UI defaults
        'clickToPayFormBgColor'                           => null,
        'clickToPayFieldBgColor'                          => null,
        'clickToPayFieldBorder'                           => null,
        'clickToPayFieldOutline'                          => null,
        'clickToPayFieldLabelColor'                       => null,
        'clickToPayFieldPlaceholderColor'                 => null,
        'clickToPayFieldTextColor'                        => null,
        'clickToPayFieldErrorCodeColor'                   => null,
        'clickToPayFontFamily'                            => null,
        'clickToPayFontUrl'                               => null,
        'clickToPayLabelStyleFontSize'                    => null,
        'clickToPayLabelStyleFontWeight'                  => null,
        'clickToPayInputStyleFontSize'                    => null,
        'clickToPayInputStyleFontWeight'                  => null,
        'clickToPayErrorValidationStyleFontSize'          => null,
        'clickToPayErrorValidationStyleFontWeight'        => null,
        'clickToPayBtnBgColor'                            => null,
        'clickToPayBtnTextColor'                          => null,
        'clickToPayBtnBorderColor'                        => null,
        'clickToPaySeparatorColor'                        => null,
        'clickToPaySeparatorTextColor'                    => null,

        // Other default settings
        'ratepayInvoiceDeviceFingerprintSnippetId'        => 'ratepay',
        'securedInvoiceAllowDifferentShippingAddress'     => false,
        'securedInstallmentAllowDifferentShippingAddress' => false,
        'securedDirectDebitAllowDifferentShippingAddress' => false,
        'amazonPayExpressRestrictPOBoxes'                 => true,
        'amazonPayExpressRestrictPackstations'            => true,

        // Default payment status mapping
        'paymentStatusAppointed'                          => StateMachineTransitionActions::ACTION_REOPEN,
        'paymentStatusCapture'                            => StateMachineTransitionActions::ACTION_PAID,
        'paymentStatusPartialCapture'                     => StateMachineTransitionActions::ACTION_PAID_PARTIALLY,
        'paymentStatusPaid'                               => StateMachineTransitionActions::ACTION_PAID,
        'paymentStatusUnderpaid'                          => StateMachineTransitionActions::ACTION_PAID_PARTIALLY,
        'paymentStatusCancelation'                        => StateMachineTransitionActions::ACTION_CANCEL,
        'paymentStatusRefund'                             => StateMachineTransitionActions::ACTION_REFUND,
        'paymentStatusPartialRefund'                      => StateMachineTransitionActions::ACTION_REFUND_PARTIALLY,
        'paymentStatusDebit'                              => StateMachineTransitionActions::ACTION_PAID,
        'paymentStatusReminder'                           => StateMachineTransitionActions::ACTION_REMIND,
        'paymentStatusVauthorization'                     => '',
        'paymentStatusVsettlement'                        => '',
        'paymentStatusTransfer'                           => StateMachineTransitionActions::ACTION_CANCEL,
        'paymentStatusInvoice'                            => StateMachineTransitionActions::ACTION_PAID,
        'paymentStatusFailed'                             => StateMachineTransitionActions::ACTION_CANCEL,
    ];

    final public const UPDATE_VALUES  = [ // Updated for 6.2
        'paymentStatusCapture'        => [ 'pay' => StateMachineTransitionActions::ACTION_PAID ],
        'paymentStatusPartialCapture' => [ 'pay_partially' => StateMachineTransitionActions::ACTION_PAID_PARTIALLY ],
        'paymentStatusPaid'           => [ 'pay' => StateMachineTransitionActions::ACTION_PAID ],
        'paymentStatusUnderpaid'      => [ 'pay_partially' => StateMachineTransitionActions::ACTION_PAID_PARTIALLY ],
        'paymentStatusDebit'          => [ 'pay' => StateMachineTransitionActions::ACTION_PAID ],
        'paymentStatusInvoice'        => [ 'pay' => StateMachineTransitionActions::ACTION_PAID ],
    ];
}
