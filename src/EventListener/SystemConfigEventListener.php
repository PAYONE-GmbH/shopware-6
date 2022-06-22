<?php

declare(strict_types=1);

namespace PayonePayment\EventListener;

use PayonePayment\Administration\Exception\RatepayProfileRequestFailedException;
use PayonePayment\PaymentHandler\PayoneRatepayDebitPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInstallmentPaymentHandler;
use PayonePayment\PaymentHandler\PayoneRatepayInvoicingPaymentHandler;
use PayonePayment\Payone\Client\Exception\PayoneRequestException;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\RatepayProfileStruct;
use Shopware\Core\System\SystemConfig\Event\BeforeSystemConfigChangedEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SystemConfigEventListener implements EventSubscriberInterface
{
    /** @var PayoneClientInterface */
    private $client;

    /** @var RequestParameterFactory */
    private $requestParameterFactory;

    /** @var SystemConfigService */
    private $systemConfigService;

    public function __construct(
        PayoneClientInterface $client,
        RequestParameterFactory $requestParameterFactory,
        SystemConfigService $systemConfigService
    ) {
        $this->client                  = $client;
        $this->requestParameterFactory = $requestParameterFactory;
        $this->systemConfigService     = $systemConfigService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeSystemConfigChangedEvent::class => 'beforeSystemConfigChanged',
        ];
    }

    public function beforeSystemConfigChanged(BeforeSystemConfigChangedEvent $event): void
    {
        $key                          = $event->getKey();
        $ratepayProfileConfigurations = [
            'PayonePayment.settings.ratepayDebitProfiles' => [
                'paymentHandler'        => PayoneRatepayDebitPaymentHandler::class,
                'responseConfiguration' => 'PayonePayment.settings.ratepayDebitProfileConfigurations',
            ],
            'PayonePayment.settings.ratepayInstallmentProfiles' => [
                'paymentHandler'        => PayoneRatepayInstallmentPaymentHandler::class,
                'responseConfiguration' => 'PayonePayment.settings.ratepayInstallmentProfileConfigurations',
            ],
            'PayonePayment.settings.ratepayInvoicingProfiles' => [
                'paymentHandler'        => PayoneRatepayInvoicingPaymentHandler::class,
                'responseConfiguration' => 'PayonePayment.settings.ratepayInvoicingProfileConfigurations',
            ],
        ];

        if (array_key_exists($key, $ratepayProfileConfigurations)) {
            $profiles = $event->getValue();

            $responses      = [];
            $uniqueProfiles = [];
            foreach ($profiles as $profile) {
                $shopId   = (int) $profile['shopId'];
                $currency = $profile['currency'];

                $profileRequest = $this->requestParameterFactory->getRequestParameter(
                    new RatepayProfileStruct(
                        $shopId,
                        $currency,
                        $event->getSalesChannelId() ?? '',
                        $ratepayProfileConfigurations[$key]['paymentHandler'],
                        AbstractRequestParameterBuilder::REQUEST_ACTION_RATEPAY_PROFILE
                    )
                );

                try {
                    $response = $this->client->request($profileRequest);
                } catch (PayoneRequestException $exception) {
                    throw new RatepayProfileRequestFailedException(
                        $shopId,
                        $currency,
                        $exception->getResponse()['error']['ErrorMessage']
                    );
                }

                $responses[$shopId]      = $response['addpaydata'];
                $uniqueProfiles[$shopId] = $profile;
            }

            $this->systemConfigService->set(
                $ratepayProfileConfigurations[$key]['responseConfiguration'],
                $responses,
                $event->getSalesChannelId()
            );
            $event->setValue(array_values($uniqueProfiles));
        }
    }
}
