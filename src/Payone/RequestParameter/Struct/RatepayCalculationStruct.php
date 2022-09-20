<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use PayonePayment\Components\Ratepay\Profile\Profile;
use PayonePayment\Payone\RequestParameter\Struct\Traits\CartTrait;
use PayonePayment\Payone\RequestParameter\Struct\Traits\RequestDataTrait;
use PayonePayment\Payone\RequestParameter\Struct\Traits\SalesChannelContextTrait;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class RatepayCalculationStruct extends AbstractRequestParameterStruct
{
    use SalesChannelContextTrait;
    use CartTrait;
    use RequestDataTrait;

    /** @var Profile */
    protected $profile;

    public function __construct(
        Cart $cart,
        RequestDataBag $requestData,
        SalesChannelContext $salesChannelContext,
        Profile $profile,
        string $paymentMethod,
        string $action = ''
    ) {
        $this->cart                = $cart;
        $this->requestData         = $requestData;
        $this->salesChannelContext = $salesChannelContext;
        $this->profile             = $profile;
        $this->paymentMethod       = $paymentMethod;
        $this->action              = $action;
    }

    public function getProfile(): Profile
    {
        return $this->profile;
    }
}
