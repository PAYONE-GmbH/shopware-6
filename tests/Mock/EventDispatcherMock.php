<?php

declare(strict_types=1);

namespace PayonePayment\Test\Mock;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EventDispatcherMock implements EventDispatcherInterface
{
    public function dispatch($event): void
    {
    }
}
