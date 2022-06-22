<?php

declare(strict_types=1);

namespace PayonePayment\Components\Ratepay;

interface ProfileServiceInterface
{
    public function getProfile(ProfileSearch $profileSearch): ?array;
}
