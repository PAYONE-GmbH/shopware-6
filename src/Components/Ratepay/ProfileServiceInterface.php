<?php

declare(strict_types=1);

namespace PayonePayment\Components\Ratepay;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface ProfileServiceInterface
{
    public function getProfile(ProfileSearch $profileSearch): ?Profile;

    public function getProfileByOrder(OrderEntity $order, string $paymentHandler): ?Profile;

    public function getProfileBySalesChannelContext(SalesChannelContext $salesChannelContext, string $paymentHandler): ?Profile;

    public function updateProfileConfiguration(string $paymentHandler, ?string $salesChannelId = null): array;
}
