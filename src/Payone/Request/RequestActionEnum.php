<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request;

enum RequestActionEnum: string
{
    case AUTHORIZE                    = 'authorization';
    case PREAUTHORIZE                 = 'preauthorization';
    case CAPTURE                      = 'capture';
    case REFUND                       = 'refund';
    case TEST                         = 'test';
    case GET_EXPRESS_CHECKOUT_DETAILS = 'getexpresscheckoutdetails';
    case SET_EXPRESS_CHECKOUT         = 'setexpresscheckout';
    case GENERIC_PAYMENT              = 'genericpayment';
    case CREDITCARD_CHECK             = 'creditcardcheck';
    case GET_FILE                     = 'getfile';
    case MANAGE_MANDATE               = 'managemandate';
    case DEBIT                        = 'debit';
    case SECURED_INSTALLMENT_OPTIONS  = 'securedInstallmentOptions';
}
