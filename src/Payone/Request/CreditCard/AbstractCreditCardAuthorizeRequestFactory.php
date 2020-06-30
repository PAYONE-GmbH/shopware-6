<?php

declare(strict_types=1);

namespace PayonePayment\Payone\Request\CreditCard;

use PayonePayment\Configuration\ConfigurationPrefixes;
use PayonePayment\Payone\Request\AbstractRequestFactory;
use PayonePayment\Payone\Request\Customer\CustomerRequest;
use PayonePayment\Payone\Request\System\SystemRequest;
use PayonePayment\Struct\PaymentTransaction;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

abstract class AbstractCreditCardAuthorizeRequestFactory extends AbstractRequestFactory
{
    /** @var AbstractCreditCardAuthorizeRequest */
    protected $creditCardRequest;

    /** @var CustomerRequest */
    protected $customerRequest;

    /** @var SystemRequest */
    protected $systemRequest;

    public function __construct(
        AbstractCreditCardAuthorizeRequest $authorizeRequest,
        CustomerRequest $customerRequest,
        SystemRequest $systemRequest
    ) {
        $this->creditCardRequest = $authorizeRequest;
        $this->customerRequest   = $customerRequest;
        $this->systemRequest     = $systemRequest;
    }

    public function getRequestParameters(
        PaymentTransaction $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $context
    ): array {
        $pseudoCardPan      = $dataBag->get('pseudoCardPan');
        $savedPseudoCardPan = $dataBag->get('savedPseudoCardPan');

        if (!empty($savedPseudoCardPan)) {
            $pseudoCardPan = $savedPseudoCardPan;
        }

        $this->requests[] = $this->systemRequest->getRequestParameters(
            $transaction->getOrder()->getSalesChannelId(),
            ConfigurationPrefixes::CONFIGURATION_PREFIX_CREDITCARD,
            $context->getContext()
        );

        $this->requests[] = $this->customerRequest->getRequestParameters(
            $context
        );

        $this->requests[] = $this->creditCardRequest->getRequestParameters(
            $transaction,
            $context->getContext(),
            $pseudoCardPan
        );

        return $this->createRequest();
    }
}
