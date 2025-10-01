<?php

declare(strict_types=1);

namespace PayonePayment\PaymentHandler;

use PayonePayment\RequestParameter\RequestParameterEnricherChain;
use PayonePayment\ResponseHandler\ResponseHandlerInterface;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;

interface PaymentHandlerInterface
{
    public static function isCapturable(array $transactionData, array $payoneTransActionData): bool;

    public static function isRefundable(array $transactionData): bool;

    public function getPaymentMethodUuid(): string;

    public function getValidationDefinitions(DataBag $dataBag, SalesChannelContext $salesChannelContext): array;

    public function validateRequestData(RequestDataBag $dataBag): void;

    public function getConfigKeyPrefix(): string;

    public function getDefaultAuthorizationMethod(): string;

    public function getRequestEnricherChain(): RequestParameterEnricherChain;

    public function getResponseHandler(): ResponseHandlerInterface;

    public function getRedirectResponse(
        SalesChannelContext $context,
        array $request,
        array $response,
    ): RedirectResponse|null;
}
