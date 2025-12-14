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

final class NewServicePartnerCreatedHookAction extends BaseAction
{

    protected AnsysService $service;

    public function __construct(
        LoggerFactory $loggerFactory,
        Session $session,
        MailService $mailService,
        Twig $twig,
        AnsysService $service
    )
    {
        parent::__construct($loggerFactory, $session, $mailService, $twig);
        $this->service = $service;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $postData = $request->getParsedBody();
            $eventData = $postData['event'];
            $this->service->adjustServicePartnerPortalUrl($eventData);
            return $response->withStatus(200);
        } catch (Throwable $e) {
            return $this->handleThrowable($e, $response);
        }
    }

}
