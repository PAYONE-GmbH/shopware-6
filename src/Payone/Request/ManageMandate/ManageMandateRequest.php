<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\ManageMandate;

use LogicException;
use RuntimeException;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\SalutationEntity;

class ManageMandateRequest
{
    public function getRequestParameters(
        SalesChannelContext $context,
        string $iban,
        string $bic
    ): array
    {
        return [
            'request' => 'managemandate',
            'clearingtype' => 'elv',
            'iban' => $iban,
            'bic' => $bic,
            'currency' => $context->getCurrency()->getIsoCode(),
        ];
    }
}
