<?php

declare(strict_types=1);

namespace PayonePayment\StoreApi\Route;

use OpenApi\Attributes as OA;
use PayonePayment\Service\CardRepositoryService;
use PayonePayment\StoreApi\Response\CardResponse;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\StoreApiResponse;
use Shopware\Core\System\SalesChannel\SuccessResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: [ '_routeScope' => [ 'store-api' ] ])]
class CardRoute extends AbstractCardRoute
{
    public function __construct(
        private readonly CardRepositoryService $cardRepository,
    ) {
    }

    #[\Override]
    public function getDecorated(): AbstractCardRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[\Override]
    #[Route(
        path: '/store-api/payone/account/card',
        name: 'store-api.payone.account.card',
        defaults: [ '_contextTokenRequired' => true, '_entity' => 'payone_payment_card' ],
        methods: [ 'GET' ],
    )]
    public function load(SalesChannelContext $context): CardResponse
    {
        $customer = $context->getCustomer();

        if (null === $customer) {
            throw CartException::customerNotLoggedIn();
        }

        $result = $this->cardRepository->getCards($customer, $context->getContext());

        return new CardResponse($result);
    }

    #[\Override]
    #[OA\Post(
        path: 'payone/account/deleteCard/{pseudoCardPan}',
        operationId: 'deleteCustomerCard',
        summary: 'Delete card from customer account',
        tags: [ 'Store API', 'CreditCard' ],
        parameters: [
            new OA\Parameter(
                name: 'pseudoCardPan',
                description: 'Pseudo Card Pan',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OA\Response(
                ref: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse'),
                response: 200,
                description: 'success',
            ),
        ],
    )]
    #[Route(
        path: '/store-api/payone/account/deleteCard/{pseudoCardPan}',
        name: 'store-api.payone.account.deleteCard',
        defaults: [ '_contextTokenRequired' => true ],
        methods: [ 'POST' ],
    )]
    public function delete(string $pseudoCardPan, SalesChannelContext $context): StoreApiResponse
    {
        if (null === $context->getCustomer()) {
            throw CartException::customerNotLoggedIn();
        }

        $this->cardRepository->removeCard(
            $context->getCustomer(),
            $pseudoCardPan,
            $context->getContext(),
        );

        return new SuccessResponse();
    }
}
