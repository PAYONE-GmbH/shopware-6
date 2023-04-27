<?php

declare(strict_types=1);

namespace PayonePayment\Controller;

use PayonePayment\Components\RedirectHandler\RedirectHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class RedirectController
{
    public function __construct(private readonly RedirectHandler $redirectHandler)
    {
    }

    /**
     * @Route("/payone/redirect", name="payment.payone_redirect", defaults={"csrf_protected": false, "_routeScope"={"storefront"}})
     */
    public function execute(Request $request): Response
    {
        $hash = $request->get('hash');

        if (empty($hash)) {
            throw new NotFoundHttpException();
        }

        try {
            $target = $this->redirectHandler->decode($hash);
        } catch (\Throwable) {
            throw new NotFoundHttpException();
        }

        return new RedirectResponse($target);
    }
}
