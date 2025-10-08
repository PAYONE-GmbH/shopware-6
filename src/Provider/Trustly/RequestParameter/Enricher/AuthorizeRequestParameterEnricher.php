<?php

declare(strict_types=1);

namespace PayonePayment\Provider\Trustly\RequestParameter\Enricher;

use PayonePayment\PaymentHandler\Enum\PayoneClearingEnum;
use PayonePayment\Payone\Dto\PaymentTransactionDto;
use PayonePayment\Payone\Request\RequestActionEnum;
use PayonePayment\RequestParameter\AbstractRequestDto;
use PayonePayment\RequestParameter\PaymentRequestDto;
use PayonePayment\RequestParameter\RequestParameterEnricherInterface;
use Shopware\Core\Checkout\Payment\PaymentException;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @implements RequestParameterEnricherInterface<PaymentRequestDto>
 */
readonly class AuthorizeRequestParameterEnricher implements RequestParameterEnricherInterface
{
    #[\Override]
    public function enrich(AbstractRequestDto $arguments): array
    {
        $requestActionEnum = $this->getRequestActionEnum();

        if ($requestActionEnum->value !== $arguments->action) {
            return [];
        }

        $dataBag            = $arguments->requestData;
        $paymentTransaction = $arguments->paymentTransaction;
        $iban               = $this->validateIbanRequestParameter($dataBag, $paymentTransaction);

        return [
            'request'                => $requestActionEnum->value,
            'clearingtype'           => PayoneClearingEnum::ONLINE_BANK_TRANSFER->value,
            'onlinebanktransfertype' => 'TRL',
            'iban'                   => $iban,
        ];
    }

    protected function getRequestActionEnum(): RequestActionEnum
    {
        return RequestActionEnum::AUTHORIZE;
    }

    private function validateIbanRequestParameter(ParameterBag $dataBag, PaymentTransactionDto $transaction): string
    {
        $iban = $dataBag->get('iban');

        if (empty($iban) || !\is_string($iban)) {
            throw PaymentException::asyncProcessInterrupted(
                $transaction->orderTransaction->getId(),
                'Missing iban parameter.',
            );
        }

        return $iban;
    }
}
