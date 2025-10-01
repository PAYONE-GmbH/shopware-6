<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\Enum;

enum CreditCardRequestParamEnum: string
{
    case SAVE_CREDIT_CARD      = 'saveCreditCard';
    case PSEUDO_CARD_PAN       = 'pseudoCardPan';
    case SAVED_PSEUDO_CARD_PAN = 'savedPseudoCardPan';
    case CARD_EXPIRE_DATE      = 'cardExpireDate';
    case CARD_TYPE             = 'cardType';
    case TRUNCATED_CARD_PAN    = 'truncatedCardPan';
    case CARD_HOLDER           = 'cardHolder';
}
