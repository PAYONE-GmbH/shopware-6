<?php

declare(strict_types=1);

namespace PayonePayment\Components\MandateService;

use DateTime;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;

interface MandateServiceInterface
{
    public function getMandates(
        CustomerEntity $customer,
        Context $context
    ): EntitySearchResult;

    public function removeMandate(
        CustomerEntity $customer,
        string $identification,
        Context $context
    ): void;

    public function saveMandate(
        CustomerEntity $customer,
        string $identification,
        DateTime $signatureDate,
        Context $context
    ): void;
}
