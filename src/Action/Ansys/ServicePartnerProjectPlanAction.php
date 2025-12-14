<?php

namespace App\Action\Ansys;

use App\Action\BaseAction;
use App\Classes\UtilityProvider;
use App\Factory\LoggerFactory;
use App\Service\Ansys\AnsysService;
use App\Service\MailService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;
use Symfony\Component\HttpFoundation\Session\Session;
use Throwable;

final class ServicePartnerProjectPlanAction extends BaseAction
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
            $this->session->set('errors', []);
            $spId = $this->session->get('spid');
            if ($spId === null) {
                return $response->withStatus(404);
            }
            $projectId = filter_var($args['pid'], FILTER_SANITIZE_NUMBER_INT);
            $queryParams = $request->getQueryParams();
            $base64Id = base64_encode($spId);
            $spData = $this->service->getItemData($spId, true);
            $spName = $spData['name'];
            if (array_key_exists('c', $queryParams) && $queryParams['c'] == $base64Id ) {
                $projectData = $this->service->getItemData($projectId, true);
                $spPpBoardId = UtilityProvider::getColumnValueFromItem($projectData, 'text_mkp79mp3', true);
                if ($spPpBoardId == '') {
                    $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                    $this->session->set('errors', [['severity' => 'danger', 'message' => 'No PP exists for this project, please upload one.']]);
                    $url = $routeParser->urlFor('spbppuploadform', [], ['c' => $base64Id]);
                    return $response->withHeader('Location', $url)->withStatus(302);
                }
                $projectPlanData = $this->service->getSpPpData($spPpBoardId, $projectData['name']);
                return $this->twig->render($response, 'ansys/service-partner-project-plan.twig', [
                    'spname' => $spName,
                    'projectname' => $projectData['name'],
                    'ppdata' => $projectPlanData
                ]);
            }
            else {
                return $response->withStatus(404);
            }
        } catch (Throwable $e) {
            return $this->handleThrowable($e, $response, 'Ansys ServicePartnerProjectPlanAction');
        }
    }

}
