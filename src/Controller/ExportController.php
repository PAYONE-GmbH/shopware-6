<?php
/**
 * Created by PhpStorm.
 * User: a.levitsky
 * Date: 15.11.2019
 * Time: 11:21
 */

namespace PayonePayment\Controller;

use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ExportController extends AbstractController
{

    /**
     * @RouteScope(scopes={"api"})
     * @Route("/api/v{version}/_action/payone_payment/export-config", name="api.action.payone_payment.export.config", methods={"POST"})
     */
    public function exportConfig():
    {
        //@toDo: get Config
        $configContent = '';
        $response = new Response($configContent);

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            'ConfigExport.xml'
        );

        $response->headers->set('Content-Disposition', $disposition);
        return new $response;
    }

}