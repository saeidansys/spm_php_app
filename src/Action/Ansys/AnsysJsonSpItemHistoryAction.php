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

final class AnsysJsonSpItemHistoryAction extends BaseAction
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
            $token = $this->service->getTokenForRoute(AnsysService::TOKEN_JSON_SP_PP_ITEM_HISTORY);
            $headers = $request->getHeaders();
            if (array_key_exists('token', $headers) && $headers['token'][0] == $token ) {
                $result = $this->service->exportAllSpItemHistoryEntries();
                $response->getBody()->write(json_encode($result));
                return $response->withHeader('Content-Type', 'application/json');
            }
            else {
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
        } catch (Throwable $e) {
            return $this->handleThrowable($e, $response, 'Ansys AnsysJsonSpItemHistoryAction');
        }
    }

}
