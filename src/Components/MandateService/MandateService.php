<?php

declare(strict_types=1);

namespace PayonePayment\Components\MandateService;

use DateTime;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class MandateService implements MandateServiceInterface
{
    /** @var EntityRepositoryInterface */
    private $mandateRepository;

    public function __construct(EntityRepositoryInterface $mandateRepository)
    {
        $this->mandateRepository = $mandateRepository;
    }

    public function getMandates(CustomerEntity $customer, Context $context): EntitySearchResult
    {
        $criteria = new Criteria();

        $criteria->addFilter(
            new EqualsFilter('payone_payment_card.customerId', $customer->getId())
        );

        return $this->mandateRepository->search($criteria, $context);
    }

    public function removeMandate(CustomerEntity $customer, string $mandate, Context $context): void
    {
        // TODO: Implement removeMandate() method.
    }

    public function saveMandate(
        CustomerEntity $customer,
        string $identification,
        DateTime $signatureDate,
        Context $context
    ): void {
        // TODO: Implement saveMandate() method.
    }
}
