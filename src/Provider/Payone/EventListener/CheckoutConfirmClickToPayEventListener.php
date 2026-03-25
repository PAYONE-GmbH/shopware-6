<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Payone\EventListener;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Provider\Payone\Dto\ClickToPayJwtDto;
use PayonePayment\Provider\Payone\PaymentMethod\ClickToPayPaymentMethod;
use PayonePayment\Provider\Payone\Service\ClickToPayJwtHandler;
use Psr\Log\LoggerInterface;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class CheckoutConfirmClickToPayEventListener implements EventSubscriberInterface
{

    public function __construct(
        private ClickToPayJwtHandler $jwtHandler,
        private ConfigReaderInterface $configReader,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class  => 'assignClickToPayData',
            AccountEditOrderPageLoadedEvent::class => 'assignClickToPayData',
        ];
    }

    public function assignClickToPayData(CheckoutConfirmPageLoadedEvent|AccountEditOrderPageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        $customer            = $salesChannelContext->getCustomer();

        if (ClickToPayPaymentMethod::UUID !== $salesChannelContext->getPaymentMethod()->getId()) {
            return;
        }

        $configuration = $this->configReader->read($salesChannelContext->getSalesChannelId());

        try {
            $jwt = $this->fetchJwt($event);
        } catch (\Throwable $e) {
            $request = $event->getRequest();

            if ($request->hasSession()) {
                $request->getSession()->getFlashBag()->add(
                    'danger',
                    $this->translator->trans('error.VIOLATION::PAYONE_INVALID_PAYMENT_METHOD'),
                );
            }

            $this->logger->error(
                'Payone CTP JWT failed',
                [ 'exception' => $e->getMessage(), 'trace' => $e->getTraceAsString() ]
            );

            return;
        }

        $uiCustomization = [
            'formBgColor'                    => $configuration->getNullableString('clickToPayFormBgColor'),
            'fieldBgColor'                   => $configuration->getNullableString('clickToPayFieldBgColor'),
            'fieldBorder'                    => $configuration->getNullableString('clickToPayFieldBorder'),
            'fieldOutline'                   => $configuration->getNullableString('clickToPayFieldOutline'),
            'fieldLabelColor'                => $configuration->getNullableString('clickToPayFieldLabelColor'),
            'fieldPlaceholderColor'          => $configuration->getNullableString('clickToPayFieldPlaceholderColor'),
            'fieldTextColor'                 => $configuration->getNullableString('clickToPayFieldTextColor'),
            'fieldErrorCodeColor'            => $configuration->getNullableString('clickToPayFieldErrorCodeColor'),
            'fontFamily'                     => $configuration->getNullableString('clickToPayFontFamily'),
            'fontUrl'                        => $configuration->getNullableString('clickToPayFontUrl'),
            'labelStyleFontSize'             => $configuration->getNullableString('clickToPayLabelStyleFontSize'),
            'labelStyleFontWeight'           => $configuration->getNullableString('clickToPayLabelStyleFontWeight'),
            'inputStyleFontSize'             => $configuration->getNullableString('clickToPayInputStyleFontSize'),
            'inputStyleFontWeight'           => $configuration->getNullableString('clickToPayInputStyleFontWeight'),
            'errorValidationStyleFontSize'   => $configuration->getNullableString(
                'clickToPayErrorValidationStyleFontSize'
            ),
            'errorValidationStyleFontWeight' => $configuration->getNullableString(
                'clickToPayErrorValidationStyleFontWeight'
            ),
            'btnBgColor'                     => $configuration->getNullableString('clickToPayBtnBgColor'),
            'btnTextColor'                   => $configuration->getNullableString('clickToPayBtnTextColor'),
            'btnBorderColor'                 => $configuration->getNullableString('clickToPayBtnBorderColor'),
            'separatorColor'                 => $configuration->getNullableString('clickToPaySeparatorColor'),
            'separatorTextColor'             => $configuration->getNullableString('clickToPaySeparatorTextColor'),
        ];

        $event->getPage()->addArrayExtension('payone-click-to-pay-options', \array_merge($uiCustomization, [
            'token'        => $jwt->token,
            'locale'       => \str_replace('-', '_', $salesChannelContext->getLanguageInfo()->localeCode),
            'currencyCode' => $salesChannelContext->getCurrency()->getShortName(),
            'amount'       => $this->getTotalAmount($event),
            'email'        => $customer?->getEmail(),
        ]));
    }

    private function fetchJwt(CheckoutConfirmPageLoadedEvent|AccountEditOrderPageLoadedEvent $event): ClickToPayJwtDto
    {
        try {
            $session = $event->getRequest()->getSession();
        } catch (SessionNotFoundException) {
            $session = null;
        }

        return $this->jwtHandler->getJwt($session, $event->getSalesChannelContext());
    }

    private function getTotalAmount(CheckoutConfirmPageLoadedEvent|AccountEditOrderPageLoadedEvent $event): string
    {
        if ($event instanceof CheckoutConfirmPageLoadedEvent) {
            $cart        = $event->getPage()->getCart();
            $precision   = $event->getSalesChannelContext()->getCurrency()->getTotalRounding()->getDecimals();
            $amountFloat = $cart->getPrice()->getTotalPrice();
        } else {
            $order       = $event->getPage()->getOrder();
            $precision   = $order->getTotalRounding()?->getDecimals()
                ?? $event->getSalesChannelContext()->getCurrency()->getTotalRounding()->getDecimals();
            $amountFloat = $order->getPrice()->getTotalPrice();
        }

        return number_format($amountFloat, $precision, '.', '');
    }
}
