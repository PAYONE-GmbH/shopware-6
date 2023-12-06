<?php

declare(strict_types=1);

namespace PayonePayment\Components\MandateService;

use PayonePayment\DataAbstractionLayer\Entity\Mandate\PayonePaymentMandateEntity;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\GetFileStruct;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class MandateService implements MandateServiceInterface
{
    public function __construct(
        private readonly EntityRepository $mandateRepository,
        private readonly PayoneClientInterface $client,
        private readonly RequestParameterFactory $requestFactory
    ) {
    }

    public function getMandates(CustomerEntity $customer, SalesChannelContext $context): EntitySearchResult
    {
        $criteria = new Criteria();

        $criteria->addFilter(
            new EqualsFilter('customerId', $customer->getId())
        );

        return $this->mandateRepository->search($criteria, $context->getContext());
    }

    public function saveMandate(
        CustomerEntity $customer,
        string $identification,
        \DateTime $signatureDate,
        SalesChannelContext $context
    ): void {
        $mandate = $this->getExistingMandate(
            $customer,
            $identification,
            $context->getContext()
        );

        $data = [
            'id' => $mandate === null ? Uuid::randomHex() : $mandate->getId(),
            'identification' => $identification,
            'signatureDate' => $signatureDate,
            'customerId' => $customer->getId(),
        ];

        $this->mandateRepository->upsert([$data], $context->getContext());
    }

    public function downloadMandate(
        CustomerEntity $customer,
        string $identification,
        SalesChannelContext $context
    ): string {
        $mandate = $this->getExistingMandate(
            $customer,
            $identification,
            $context->getContext()
        );

        if ($mandate === null) {
            throw new FileNotFoundException('mandate not found');
        }

        $request = $this->requestFactory->getRequestParameter(
            new GetFileStruct(
                $context,
                PayoneDebitPaymentHandler::class,
                $mandate->getIdentification()
            )
        );

        try {
            $response = $this->client->request($request, false);
        } catch (\Throwable) {
            throw new \RuntimeException('mandate not found');
        }

        return (string) $response['data'];
    }

    public function removeAllMandatesForCustomer(CustomerEntity $customer, SalesChannelContext $context): void
    {
        $mandates = $this->getMandates($customer, $context);

        $ids = array_map(static fn ($item) => ['id' => $item], array_values($mandates->getIds()));

        $this->mandateRepository->delete($ids, $context->getContext());
    }

    protected function getExistingMandate(
        CustomerEntity $customer,
        string $identification,
        Context $context
    ): ?PayonePaymentMandateEntity {
        $criteria = new Criteria();

        $criteria->addFilter(
            new EqualsFilter('identification', $identification)
        );

        $criteria->addFilter(
            new EqualsFilter('customerId', $customer->getId())
        );

        return $this->mandateRepository->search($criteria, $context)->first();
    }
}
