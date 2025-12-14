<?php

namespace App\Action\Ansys;

use App\Action\BaseAction;
use App\Factory\LoggerFactory;
use App\Service\Ansys\AnsysService;
use App\Service\MailService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;
use Symfony\Component\HttpFoundation\Session\Session;
use Throwable;

final class ServicePartnerLoginSubmitAction extends BaseAction
{
    private AnsysService $service;

    public function __construct(
        LoggerFactory $logger,
        Session $session,
        Twig $twig,
        MailService $mailer,
        AnsysService $service
    ){
        parent::__construct($logger, $session, $mailer, $twig);
        $this->service = $service;
        $this->errors = [];
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {

        try {
            $parsedBody = $request->getParsedBody();
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            $servicePartnerData = $this->service->authorizeServicePartner($parsedBody);
            $errors = $this->session->get('loginerrors', []);
            if (!empty($errors)) {
                $spId = $parsedBody['spid'];
                $url = $routeParser->urlFor('sploginform', ['id' => $spId], ['c' => base64_encode($spId)]);
                return $response->withHeader('Location', $url)->withStatus(302);
            }
            if ($servicePartnerData === null) {
                return $response->withStatus(404);
            }
            $url = $routeParser->urlFor('spdashboard', ['id' => $servicePartnerData['id']],
                ['c' => base64_encode($servicePartnerData['id'])]);
            $this->session->set('spid', $servicePartnerData['id']);
            $this->session->set('spname', $servicePartnerData['name']);
            $this->session->remove('loginerrors');
            return $response->withHeader('Location', $url)->withStatus(302);
        } catch (Throwable $e) {
            return $this->handleThrowable($e, $response, 'Ansys ServicePartnerLoginSubmitAction');
        }
    }

}
