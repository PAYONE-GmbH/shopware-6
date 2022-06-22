<?php

declare(strict_types=1);

namespace PayonePayment\Components\Ratepay;

use PayonePayment\Components\ConfigReader\ConfigReaderInterface;
use PayonePayment\Payone\Client\PayoneClientInterface;
use PayonePayment\Payone\RequestParameter\Builder\AbstractRequestParameterBuilder;
use PayonePayment\Payone\RequestParameter\RequestParameterFactory;
use PayonePayment\Payone\RequestParameter\Struct\RatepayProfileStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ProfileService implements ProfileServiceInterface
{
    /** @var PayoneClientInterface */
    private $client;

    /** @var RequestParameterFactory */
    private $requestParameterFactory;

    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(
        PayoneClientInterface $client,
        RequestParameterFactory $requestParameterFactory,
        ConfigReaderInterface $configReader
    ) {
        $this->client                  = $client;
        $this->requestParameterFactory = $requestParameterFactory;
        $this->configReader            = $configReader;
    }

    public function loadProfileConfiguration(string $paymentMethod, ?SalesChannelContext $salesChannelContext = null): void
    {
//        $profileRequest = $this->requestParameterFactory->getRequestParameter(
//            new RatepayProfileStruct(
//                $paymentMethod,
//                AbstractRequestParameterBuilder::REQUEST_ACTION_RATEPAY_PROFILE
//            )
//        );
//
//        $this->client->request($profileRequest);
    }
}
