<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\CreditCardCheck;

use PayonePayment\Payone\Struct\PaymentTransactionStruct;
use RuntimeException;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Language\LanguageEntity;
use Symfony\Component\HttpFoundation\RequestStack;

class CreditCardCheckRequest
{
    public function getRequestParameters(): array
    {
        return [
            'request' => 'creditcardcheck',
            'storecarddata' => 'yes',
        ];
    }
}
