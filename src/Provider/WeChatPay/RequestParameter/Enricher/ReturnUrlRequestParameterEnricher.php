<?php

declare(strict_types=1);

namespace PayonePayment\Provider\WeChatPay\RequestParameter\Enricher;

use PayonePayment\Components\RedirectHandler\RedirectHandler;
use PayonePayment\RequestParameter\Enricher\ReturnUrlRequestParameterEnricherTrait;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;

readonly class ReturnUrlRequestParameterEnricher implements RequestParameterEnricherInterface
{
    use ReturnUrlRequestParameterEnricherTrait;

    public function __construct(
        protected RedirectHandler $redirectHandler,
    ) {
    }
}
