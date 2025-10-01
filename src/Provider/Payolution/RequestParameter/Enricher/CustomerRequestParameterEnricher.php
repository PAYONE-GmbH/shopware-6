<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payolution\RequestParameter\Enricher;

use PayonePayment\RequestParameter\Enricher\CustomerRequestParameterEnricherTrait;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class CustomerRequestParameterEnricher implements RequestParameterEnricherInterface
{
    use CustomerRequestParameterEnricherTrait;

    public function __construct(
        protected EntityRepository $languageRepository,
        protected EntityRepository $salutationRepository,
        protected EntityRepository $countryRepository,
        protected RequestStack $requestStack,
    ) {
    }
}
