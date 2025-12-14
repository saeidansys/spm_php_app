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

final class ServicePartnerDashboardAction extends BaseAction
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
                $this->session->remove('uploadeddata');
                $this->session->remove('errors');
                $projectData = $this->service->getItemData($spId, true);
                $spName = $projectData['name'];
                $projects = $this->service->getProjectsForServicePartner($spName);
                return $this->twig->render($response, 'ansys/service-partner-dashboard.twig', [
                    'spname' => $spName,
                    'projects' => $projects,
                    'b64' => $base64Id
                ]);
            }
            else {
                return $response->withStatus(403);
            }
        } catch (Throwable $e) {
            return $this->handleThrowable($e, $response, 'Ansys ServicePartnerDashboardAction');
        }
    }

}
