<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Builder;

use PayonePayment\PaymentHandler\AbstractKlarnaPaymentHandler;
use PayonePayment\PaymentHandler\AbstractPostfinancePaymentHandler;
use PayonePayment\PaymentHandler\PayoneAlipayPaymentHandler;
use PayonePayment\PaymentHandler\PayoneBancontactPaymentHandler;
use PayonePayment\PaymentHandler\PayoneCreditCardPaymentHandler;
use PayonePayment\PaymentHandler\PayoneDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneEpsPaymentHandler;
use PayonePayment\PaymentHandler\PayoneIDealPaymentHandler;
use PayonePayment\PaymentHandler\PayoneOpenInvoicePaymentHandler;
use PayonePayment\PaymentHandler\PayonePaydirektPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayonePayolutionInvoicingPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaypalExpressPaymentHandler;
use PayonePayment\PaymentHandler\PayonePaypalPaymentHandler;
use PayonePayment\PaymentHandler\PayonePrepaymentPaymentHandler;
use PayonePayment\PaymentHandler\PayonePrzelewy24PaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInvoicingPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecuredDirectDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecuredInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecuredInvoicePaymentHandler;
use PayonePayment\PaymentHandler\PayoneSecureInvoicePaymentHandler;
use PayonePayment\PaymentHandler\PayoneSofortBankingPaymentHandler;
use PayonePayment\PaymentHandler\PayoneTrustlyPaymentHandler;
use PayonePayment\PaymentHandler\PayoneWeChatPayPaymentHandler;
use PayonePayment\Payone\RequestParameter\Struct\AbstractRequestParameterStruct;
use PayonePayment\Payone\RequestParameter\Struct\KlarnaCreateSessionStruct;
use PayonePayment\Payone\RequestParameter\Struct\ManageMandateStruct;
use PayonePayment\Payone\RequestParameter\Struct\PaymentTransactionStruct;
use PayonePayment\Payone\RequestParameter\Struct\PayolutionAdditionalActionStruct;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\SalutationEntity;
use Symfony\Component\HttpFoundation\RequestStack;

class CustomerRequestParameterBuilder extends AbstractRequestParameterBuilder
{
    private EntityRepositoryInterface $languageRepository;

    private RequestStack $requestStack;

    private EntityRepositoryInterface $salutationRepository;

    private EntityRepositoryInterface $countryRepository;

    public function __construct(
        EntityRepositoryInterface $languageRepository,
        EntityRepositoryInterface $salutationRepository,
        EntityRepositoryInterface $countryRepository,
        RequestStack $requestStack
    ) {
        $this->languageRepository = $languageRepository;
        $this->salutationRepository = $salutationRepository;
        $this->countryRepository = $countryRepository;
        $this->requestStack = $requestStack;
    }

    /**
     * @param KlarnaCreateSessionStruct|ManageMandateStruct|PaymentTransactionStruct|PayolutionAdditionalActionStruct $arguments
     */
    public function getRequestParameter(AbstractRequestParameterStruct $arguments): array
    {
        $this->validateMethod($arguments, 'getSalesChannelContext');
        $salesChannelContext = $arguments->getSalesChannelContext();

        if ($salesChannelContext->getCustomer() === null) {
            throw new \RuntimeException('missing customer');
        }

        $language = $this->getCustomerLanguage($salesChannelContext);

        if ($language->getLocale() === null) {
            throw new \RuntimeException('missing language locale');
        }

        $billingAddress = $salesChannelContext->getCustomer()->getActiveBillingAddress();

        if ($billingAddress === null) {
            throw new \RuntimeException('missing customer billing address');
        }

        $personalData = [
            'company' => $billingAddress->getCompany(),
            'salutation' => $this->getCustomerSalutation($billingAddress, $salesChannelContext->getContext())->getDisplayName(),
            'title' => $billingAddress->getTitle(),
            'firstname' => $billingAddress->getFirstName(),
            'lastname' => $billingAddress->getLastName(),
            'street' => $billingAddress->getStreet(),
            'addressaddition' => $billingAddress->getAdditionalAddressLine1(),
            'zip' => $billingAddress->getZipcode(),
            'city' => $billingAddress->getCity(),
            'country' => $this->getCustomerCountry($billingAddress, $salesChannelContext->getContext())->getIso(),
            'email' => $salesChannelContext->getCustomer()->getEmail(),
            'language' => substr($language->getLocale()->getCode(), 0, 2),
            'ip' => $this->requestStack->getCurrentRequest() !== null ? $this->requestStack->getCurrentRequest()->getClientIp() : null,
        ];

        $birthday = $salesChannelContext->getCustomer()->getBirthday();

        if ($birthday !== null) {
            $personalData['birthday'] = $birthday->format('Ymd');
        }

        return array_filter($personalData);
    }

    public function supports(AbstractRequestParameterStruct $arguments): bool
    {
        if ($arguments instanceof PayolutionAdditionalActionStruct
            || $arguments instanceof ManageMandateStruct
            || $arguments instanceof KlarnaCreateSessionStruct
        ) {
            return true;
        }

        if (!($arguments instanceof PaymentTransactionStruct)) {
            return false;
        }

        $paymentMethod = $arguments->getPaymentMethod();

        switch ($paymentMethod) {
            case PayonePaypalPaymentHandler::class:
            case PayonePaypalExpressPaymentHandler::class:
            case PayoneSofortBankingPaymentHandler::class:
            case PayoneDebitPaymentHandler::class:
            case PayoneCreditCardPaymentHandler::class:
            case PayonePayolutionDebitPaymentHandler::class:
            case PayonePayolutionInstallmentPaymentHandler::class:
            case PayonePayolutionInvoicingPaymentHandler::class:
            case PayoneTrustlyPaymentHandler::class:
            case PayoneEpsPaymentHandler::class:
            case PayoneIDealPaymentHandler::class:
            case PayoneBancontactPaymentHandler::class:
            case PayonePaydirektPaymentHandler::class:
            case PayoneSecureInvoicePaymentHandler::class:
            case PayoneOpenInvoicePaymentHandler::class:
            case PayonePrepaymentPaymentHandler::class:
            case PayoneRatepayDebitPaymentHandler::class:
            case PayoneRatepayInstallmentPaymentHandler::class:
            case PayoneRatepayInvoicingPaymentHandler::class:
            case PayonePrzelewy24PaymentHandler::class:
            case PayoneWeChatPayPaymentHandler::class:
            case PayoneAlipayPaymentHandler::class:
            case PayoneSecuredInvoicePaymentHandler::class:
            case PayoneSecuredInstallmentPaymentHandler::class:
            case PayoneSecuredDirectDebitPaymentHandler::class:
                return true;
        }

        if (is_subclass_of($paymentMethod, AbstractKlarnaPaymentHandler::class)) {
            return true;
        }

        if (is_subclass_of($paymentMethod, AbstractPostfinancePaymentHandler::class)) {
            return true;
        }

        return false;
    }

    private function getCustomerSalutation(CustomerAddressEntity $addressEntity, Context $context): SalutationEntity
    {
        $salutationId = $addressEntity->getSalutationId();
        if ($salutationId === null) {
            throw new \RuntimeException('missing order customer salutation');
        }

        $criteria = new Criteria([$salutationId]);

        $salutation = $this->salutationRepository->search($criteria, $context)->first();

        if ($salutation === null) {
            throw new \RuntimeException('missing order customer salutation');
        }

        return $salutation;
    }

    private function getCustomerCountry(CustomerAddressEntity $addressEntity, Context $context): CountryEntity
    {
        $criteria = new Criteria([$addressEntity->getCountryId()]);

        /** @var CountryEntity|null $country */
        $country = $this->countryRepository->search($criteria, $context)->first();

        if ($country === null) {
            throw new \RuntimeException('missing order country entity');
        }

        return $country;
    }

    private function getCustomerLanguage(SalesChannelContext $context): LanguageEntity
    {
        if ($context->getCustomer() === null) {
            throw new \RuntimeException('missing customer');
        }

        $criteria = new Criteria([$context->getCustomer()->getLanguageId()]);
        $criteria->addAssociation('locale');

        /** @var LanguageEntity|null $language */
        $language = $this->languageRepository->search($criteria, $context->getContext())->first();

        if ($language === null) {
            throw new \RuntimeException('missing customer language');
        }

        return $language;
    }
}
