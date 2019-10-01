<?php

declare(strict_types=1);

namespace PayonePayment\Storefront\Controller\Paysafe;

use PayonePayment\Components\CartHasher\CartHasherInterface;
use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\PaymentMethod\PayonePaypalExpress;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\Request\Paypal\PaypalGetExpressCheckoutDetailsRequestFactory;
use PayonePayment\Payone\Request\Paypal\PaypalSetExpressCheckoutRequestFactory;
use PayonePayment\Storefront\Struct\PaypalExpressCartData;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountRegistrationService;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannel\SalesChannelContextSwitcher;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\SalutationEntity;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class PaysafeInvoicingController extends StorefrontController
{
    private const URL = 'https://payment.payolution.com/payolution-payment/infoport/dataprivacydeclaration?mId=';

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(ConfigReaderInterface $configReader, LoggerInterface $logger)
    {
        $this->configReader = $configReader;
        $this->logger = $logger;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/payone/invoicing-consent", name="frontend.account.payone.paysafe.invoicing-consent", options={"seo": "false"}, methods={"GET", "POST"}, defaults={"id"=null, "XmlHttpRequest"=true})
     */
    public function fetchConsentModalContent(SalesChannelContext $context): Response
    {
        $configuration = $this->configReader->read($context->getSalesChannel()->getId());

        $companyName = $configuration->get('paysafeCompanyName');

        if (empty($companyName)) {
            $this->logger->error('Could not fetch invoicing consent modal content - paysafe company name is empty.');

            throw new NotFoundHttpException();
        }

        /** @var string $content */
        $content = (string) file_get_contents(self::URL . base64_encode($companyName));

        if (empty($content)) {
            $this->logger->error('Could not fetch invoicing consent modal content, paysafe returned a empty response.');

            throw new NotFoundHttpException();
        }

        return new Response($content);
    }
}
