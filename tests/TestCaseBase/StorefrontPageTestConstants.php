<?php

declare(strict_types=1);

namespace PayonePayment\TestCaseBase;

/**
 * Copied from https://github.com/shopware/shopware/blob/trunk/tests/integration/Storefront/Page/StorefrontPageTestConstants.php
 */
final class StorefrontPageTestConstants
{
    public const CUSTOMER_FIRSTNAME = 'Max';

    public const PAYMENT_METHOD_COUNT = 1;

    public const PRODUCT_NAME = 'test';

    public const SHIPPING_METHOD_COUNT = 2;

    public const AVAILABLE_SHIPPING_METHOD_COUNT = 1;

    public const AVAILABLE_PAYMENT_METHOD_COUNT = 1;

    public const COUNTRY_COUNT = 1;

    private function __construct()
    {
    }
}
