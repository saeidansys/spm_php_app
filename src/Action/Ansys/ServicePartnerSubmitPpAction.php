<?php

namespace App\Action\Ansys;

use App\Action\BaseAction;
use App\Factory\LoggerFactory;
use App\Service\Ansys\AnsysService;
use App\Service\MailService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;
use Symfony\Component\HttpFoundation\Session\Session;
use Throwable;

final class ServicePartnerSubmitPpAction extends BaseAction
{

    protected AnsysService $service;

    public function __construct(
        LoggerFactory $loggerFactory,
        Session $session,
        MailService $mailService,
        Twig $twig,
        AnsysService $service
    ){
        parent::__construct($loggerFactory, $session, $mailService, $twig);
        $this->service = $service;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $args): ResponseInterface
    {
        try {
            $spId = $this->session->get('spid');
            if ($spId === null) {
                return $response->withStatus(404);
            }
            $queryParams = $request->getQueryParams();
            $base64Id = base64_encode($spId);
            if (array_key_exists('c', $queryParams) && $queryParams['c'] == $base64Id ) {
                $this->service->processFullPpData();
                $uploadedData = $this->session->get('uploadeddata');
                $this->session->remove('uploadeddata');
                return $this->twig->render($response, 'ansys/sp-pp-submitted.twig', [
                    'spid64' => base64_encode($spId),
                    'spid' => $spId,
                    'uploadeddata' => $uploadedData
                ]);
            }
            else {
                return $response->withStatus(404);
            }
        } catch (Throwable $e) {
            return $this->handleThrowable($e, $response, 'ANSYS ServicePartnerSubmitPpAction');
        }
    }

}
