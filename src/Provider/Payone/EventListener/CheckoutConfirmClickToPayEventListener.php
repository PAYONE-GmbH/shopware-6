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

        $event->getPage()->addArrayExtension('payone-click-to-pay-options', [
            'token'                    => $jwt->token,
            'visaSrcInitiatorId'       => $configuration->getString('clickToPayVisaSrcInitiatorId'),
            'visaSrcDpaId'             => $configuration->getString('clickToPayVisaSrcDpaId'),
            'visaEncryptionKey'        => $configuration->getString('clickToPayVisaEncryptionKey'),
            'visaNModulus'             => $configuration->getString('clickToPayVisaNModulus'),
            'mastercardSrcInitiatorId' => $configuration->getString('clickToPayMastercardSrcInitiatorId'),
            'mastercardSrcDpaId'       => $configuration->getString('clickToPayMastercardSrcDpaId'),
            'buttonStyle'              => $configuration->getString('clickToPayButtonStyle'),
            'buttonTextCase'           => $configuration->getString('clickToPayButtonTextCase'),
            'buttonAndBadgeColor'      => $configuration->getString('clickToPayButtonAndBadgeColor'),
            'buttonFilledHoverColor'   => $configuration->getString('clickToPayButtonFilledHoverColor'),
            'buttonOutlinedHoverColor' => $configuration->getString('clickToPayButtonOutlinedHoverColor'),
            'buttonDisabledColor'      => $configuration->getString('clickToPayButtonDisabledColor'),
            'cardItemActiveColor'      => $configuration->getString('clickToPayCardItemActiveColor'),
            'buttonAndBadgeTextColor'  => $configuration->getString('clickToPayButtonAndBadgeTextColor'),
            'linkTextColor'            => $configuration->getString('clickToPayLinkTextColor'),
            'accentColor'              => $configuration->getString('clickToPayAccentColor'),
            'fontFamily'               => $configuration->getString('clickToPayFontFamily'),
            'buttonAndInputRadius'     => $configuration->getString('clickToPayButtonAndInputRadius'),
            'cardItemRadius'           => $configuration->getString('clickToPayCardItemRadius'),
            'currencyCode'             => $salesChannelContext->getCurrency()->getShortName(),
            'amount'                   => $this->getTotalAmount($event),
        ]);
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
