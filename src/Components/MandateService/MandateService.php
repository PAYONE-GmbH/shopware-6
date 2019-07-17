<?php

declare(strict_types=1);

namespace PayonePayment\Components\MandateService;

use DateTime;
use PayonePayment\DataAbstractionLayer\Entity\Mandate\PayonePaymentMandateEntity;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\GetFile\GetFileRequestFactory;
use RuntimeException;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Throwable;

class MandateService implements MandateServiceInterface
{
    /** @var EntityRepositoryInterface */
    private $mandateRepository;

    /** @var PayoneClientInterface */
    private $client;

    /** @var GetFileRequestFactory */
    private $requestFactory;

    public function __construct(
        EntityRepositoryInterface $mandateRepository,
        PayoneClientInterface $client,
        GetFileRequestFactory $requestFactory
    ) {
        $this->mandateRepository = $mandateRepository;
        $this->client            = $client;
        $this->requestFactory    = $requestFactory;
    }

    public function getMandates(CustomerEntity $customer, SalesChannelContext $context): EntitySearchResult
    {
        $criteria = new Criteria();

        $criteria->addFilter(
            new EqualsFilter('payone_payment_mandate.customerId', $customer->getId())
        );

        return $this->mandateRepository->search($criteria, $context->getContext());
    }

    public function saveMandate(
        CustomerEntity $customer,
        string $identification,
        DateTime $signatureDate,
        SalesChannelContext $context
    ): void {
        $mandate = $this->getExistingMandate(
            $customer,
            $identification,
            $context->getContext()
        );

        $data = [
            'id'             => null === $mandate ? Uuid::randomHex() : $mandate->getId(),
            'identification' => $identification,
            'signatureDate'  => $signatureDate,
            'customerId'     => $customer->getId(),
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

        if (null === $mandate) {
            throw new RuntimeException('mandate not found');
        }

        $request = $this->requestFactory->getRequestParameters(
            $mandate->getIdentification(),
            $context
        );

        try {
            $response = $this->client->request($request, false);
        } catch (PayoneRequestException $exception) {
            throw new RuntimeException('mandate not found');
        } catch (Throwable $exception) {
            throw new RuntimeException('mandate not found');
        }

        return (string) $response['data'];
    }

    protected function getExistingMandate(
        CustomerEntity $customer,
        string $identification,
        Context $context
    ): ?PayonePaymentMandateEntity {
        $criteria = new Criteria();

        $criteria->addFilter(
            new EqualsFilter('payone_payment_mandate.identification', $identification)
        );

        $criteria->addFilter(
            new EqualsFilter('payone_payment_mandate.customerId', $customer->getId())
        );

        return $this->mandateRepository->search($criteria, $context)->first();
    }
}
