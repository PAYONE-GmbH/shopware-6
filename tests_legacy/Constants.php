<?php

declare(strict_types=1);

namespace PayonePayment\Test;

interface Constants
{
    public const CURRENCY_ID                = '9d185b6a82224319a326a0aed4f80d0a';
    public const CURRENCY_ISO               = 'EUR';
    public const CURRENCY_DECIMAL_PRECISION = 2;
    public const CURRENCY_TAX_RATE          = 19.00;

    public const ROUNDING_INTERVAL = 1;

    public const ORDER_ID     = 'c23b44f2778240c7ad09bee356004503';
    public const ORDER_NUMBER = 'pp_on_1';

    public const LINE_ITEM_ID         = 'd2674a44955111eabb370242ac130002';
    public const LINE_ITEM_IDENTIFIER = 'LineItemIdentifier';
    public const LINE_ITEM_TYPE       = 'product';
    public const LINE_ITEM_UNIT_PRICE = 1.23;
    public const LINE_ITEM_LABEL      = 'LineItemLabel';
    public const LINE_ITEM_QUANTITY   = 4;

    public const ORDER_TRANSACTION_ID  = '4c8a04d0ae374bdbac305d717cdaf9c6';
    public const PAYONE_TRANSACTION_ID = 'test-transaction-id';
    public const COUNTRY_ID            = 'ea3a25f690f848359a4d64c1c46077ea';
    public const SALUTATION_ID         = 'c954f5baf0894e95a7ed8b172e59b145';
}
