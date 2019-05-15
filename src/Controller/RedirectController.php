<?php

declare(strict_types=1);

namespace PayonePayment\Controller;

use PayonePayment\Components\RedirectHandler\RedirectHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class RedirectController
{
    /** @var RedirectHandler */
    private $redirectHandler;

    public function __construct(RedirectHandler $redirectHandler)
    {
        $this->redirectHandler = $redirectHandler;
    }

    /**
     * @Route("/payone/redirect", name="payone_redirect")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function execute(Request $request): Response
    {
        $hash = $request->get('hash');

        if (empty($hash)) {
            throw new NotFoundHttpException();
        }

        try {
            $target = $this->redirectHandler->decode($hash);
        } catch (Throwable $exception) {
            throw new NotFoundHttpException();
        }

        return new RedirectResponse($target);
    }
}
