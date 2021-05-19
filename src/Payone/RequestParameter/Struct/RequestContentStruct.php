<?php

declare(strict_types=1);

namespace PayonePayment\Payone\RequestParameter\Struct;

use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Framework\Struct\Struct;

class RequestContentStruct extends Struct {

    protected string $action;

    protected ?string $paymentMethod = null;

    protected ?float $amount;

    protected ?string $isoCode;

    protected ?string $referenceNumber;

    protected ?PaymentTransaction $paymentTransaction;

    protected ?string $workOrderId;

    protected ?CustomerAddressEntity $shippingAddress;

}
