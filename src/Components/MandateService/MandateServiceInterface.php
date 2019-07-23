<?php

declare(strict_types=1);

namespace PayonePayment\Components\MandateService;

use DateTime;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface MandateServiceInterface
{
    public function getMandates(
        CustomerEntity $customer,
        SalesChannelContext $context
    ): EntitySearchResult;

    public function saveMandate(
        CustomerEntity $customer,
        string $identification,
        DateTime $signatureDate,
        SalesChannelContext $context
    ): void;

    public function downloadMandate(
        CustomerEntity $customer,
        string $identification,
        SalesChannelContext $context
    ): string;
}
