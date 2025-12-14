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

final class ServicePartnerSubmitUploadEditsAction extends BaseAction
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
            $data = json_decode($request->getParsedBody()['editedData'], true);
            $uploadResultArray = $this->service->processUploadEdits($data);
            $uploadNotifications = $uploadResultArray['notifications'];
            $newErrors = $uploadResultArray['newerrors'];
            $uploadedData = $this->session->get('uploadeddata');
            return $this->twig->render($response, 'ansys/sp-pp-upload-result.twig', [
                'spid' => $spId,
                'spid64' => base64_encode($spId),
                'notifications' => $uploadNotifications,
                'newerrors' => $newErrors,
                'uploadeddata' => $uploadedData
            ]);
        } catch (Throwable $e) {
            return $this->handleThrowable($e, $response, 'ANSYS ServicePartnerSubmitUploadEditsAction');
        }
    }

}
