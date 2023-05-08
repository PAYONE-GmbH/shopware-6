<?php

declare(strict_types=1);

namespace PayonePayment\StoreApi\Route;

use OpenApi\Annotations as OA;
use PayonePayment\Components\CardRepository\CardRepositoryInterface;
use PayonePayment\StoreApi\Response\CardResponse;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\StoreApiResponse;
use Shopware\Core\System\SalesChannel\SuccessResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['store-api']])]
class CardRoute extends AbstractCardRoute
{
    public function __construct(private readonly CardRepositoryInterface $cardRepository)
    {
    }

    public function getDecorated(): AbstractCardRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/payone/account/card', name: 'store-api.payone.account.card', defaults: ['_contextTokenRequired' => true, '_entity' => 'payone_payment_card'], methods: ['GET'])]
    public function load(SalesChannelContext $context): CardResponse
    {
        $customer = $context->getCustomer();

        if ($customer === null) {
            throw CartException::customerNotLoggedIn();
        }

        $result = $this->cardRepository->getCards($customer, $context->getContext());

        return new CardResponse($result);
    }

    /**
     * @OA\Post(
     *     path="payone/account/deleteCard/{pseudoCardPan}",
     *     summary="Delete card from customer account",
     *     operationId="deleteCustomerCard",
     *     tags={"Store API", "CreditCard"},
     *     @OA\Parameter(
     *         name="pseudoCardPan",
     *         in="path",
     *         description="Pseudo Card Pan",
     *         @OA\Schema(type="string"),
     *         required=true
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     )
     * )
     */
    #[Route(path: '/store-api/payone/account/deleteCard/{pseudoCardPan}', name: 'store-api.payone.account.deleteCard', defaults: ['_contextTokenRequired' => true], methods: ['POST'])]
    public function delete(string $pseudoCardPan, SalesChannelContext $context): StoreApiResponse
    {
        if ($context->getCustomer() === null) {
            throw CartException::customerNotLoggedIn();
        }

        $this->cardRepository->removeCard(
            $context->getCustomer(),
            $pseudoCardPan,
            $context->getContext()
        );

        return new SuccessResponse();
    }
}
