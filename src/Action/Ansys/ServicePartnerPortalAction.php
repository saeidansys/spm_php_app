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

final class ServicePartnerPortalAction extends BaseAction
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
            $spId = filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT);
            $queryParams = $request->getQueryParams();
            $base64Id = base64_encode($spId);
            $spData = $this->service->getItemData($spId, true);
            $spName = $spData['name'];
            if (array_key_exists('c', $queryParams) && $queryParams['c'] == $base64Id ) {
                $loginErrors = $this->session->get('loginerrors', []);
                $this->session->remove('loginerrors');
                return $this->twig->render($response, 'ansys/service-partner-portal.twig',
                    ['spname' => $spName, 'loginerrors' => $loginErrors, 'spid' => $spId]);
            }
            else {
                return $response->withStatus(404);
            }
        } catch (Throwable $e) {
            return $this->handleThrowable($e, $response, 'Ansys ServicePartnerPortalAction');
        }
    }

}
