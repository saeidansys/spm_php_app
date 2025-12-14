<?php

namespace App\Middleware;

use App\Factory\LoggerFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Response;

final class ChallengeResolver implements MiddlewareInterface
{

    protected LoggerInterface $logger;

    public function __construct(LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->addFileHandler('ansys.log')->createLogger();
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface{

        $this->logger->debug(json_encode($request->getParsedBody()));

        $postData = $request->getParsedBody();
        if (array_key_exists('challenge', $postData)) {
            $response = new Response();
            $response->getBody()->write(json_encode(['challenge' => $postData['challenge']]));
            return $response;
        }
        return $handler->handle($request);
    }

}
