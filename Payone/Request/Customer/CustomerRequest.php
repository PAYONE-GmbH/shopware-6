<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\Customer;

use PayonePayment\Payone\Request\RequestInterface;
use PayonePayment\Payone\Request\System\SystemRequest;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Language\LanguageEntity;
use Symfony\Component\HttpFoundation\RequestStack;

class CustomerRequest implements RequestInterface
{
    /** @var EntityRepositoryInterface */
    private $orderCustomerRepository;

    /** @var EntityRepositoryInterface */
    private $languageRepository;

    /** @var RequestStack */
    private $requestStack;

    public function __construct(
        EntityRepositoryInterface $orderCustomerRepository,
        EntityRepositoryInterface $languageRepository,
        RequestStack $requestStack
    ) {
        $this->orderCustomerRepository = $orderCustomerRepository;
        $this->languageRepository      = $languageRepository;
        $this->requestStack            = $requestStack;
    }

    public function getParentRequest(): string
    {
        return SystemRequest::class;
    }

    public function getRequestParameters(PaymentTransactionStruct $transaction, Context $context): array
    {
        $criteria = new Criteria([$transaction->getOrder()->getOrderCustomerId()]);
        $criteria->addAssociation('order_customer.customer');

        /** @var OrderCustomerEntity $orderCustomer */
        $orderCustomer = $this->orderCustomerRepository->search($criteria, $context)->first();

        $languages = $context->getLanguageIdChain();
        $criteria  = new Criteria([reset($languages)]);
        $criteria->addAssociation('language.locale');

        /** @var LanguageEntity $language */
        $language = $this->languageRepository->search($criteria, $context)->first();

        $address        = $transaction->getOrder()->getAddresses();
        $billingAddress = $address->get($transaction->getOrder()->getBillingAddressId());

        $personalData = [
            'salutation'      => $billingAddress->getSalutation(),
            'title'           => $billingAddress->getTitle(),
            'firstname'       => $billingAddress->getFirstName(),
            'lastname'        => $billingAddress->getLastName(),
            'street'          => $billingAddress->getStreet(),
            'addressaddition' => $billingAddress->getAdditionalAddressLine1(),
            'zip'             => $billingAddress->getZipcode(),
            'city'            => $billingAddress->getCity(),
            'country'         => $billingAddress->getCountry()->getIso(),
            'email'           => $transaction->getOrder()->getOrderCustomer()->getEmail(),
            'language'        => substr($language->getLocale()->getCode(), 0, 2),
            'gender'          => $transaction->getOrder()->getOrderCustomer()->getSalutation() === 'Herr' ? 'm' : 'f',
            'ip'              => $this->requestStack->getCurrentRequest() ? $this->requestStack->getCurrentRequest()->getClientIp() : null,
        ];

        if (null !== $orderCustomer->getCustomer()->getBirthday()) {
            $personalData['birthday'] = $orderCustomer->getCustomer()->getBirthday()->format('Ymd');
        }

        return $personalData;
    }
}
