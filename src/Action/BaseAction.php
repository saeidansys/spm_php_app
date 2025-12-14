<?php

namespace App\Action;

use App\Factory\LoggerFactory;
use App\Service\MailService;
use PHPMailer\PHPMailer\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Symfony\Component\HttpFoundation\Session\Session;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class BaseAction
{

    protected LoggerInterface $logger;
    protected Session $session;
    protected MailService $mailer;
    protected Twig $twig;
    protected array $errors;

    public function __construct(LoggerFactory $loggerFactory, Session $session, MailService $mailService, Twig $twig)
    {
        $this->logger = $loggerFactory->addFileHandler('app.log')->createLogger();
        $this->session = $session;
        $this->mailer = $mailService;
        $this->twig = $twig;
        $this->errors = [];
    }

    protected function handleThrowable(Throwable $e, ResponseInterface $response, string $identifier = 'General'): ResponseInterface
    {
        $message = $e->getMessage();
        $code = $e->getCode();
        $traceAsString = $e->getTraceAsString();
        $this->logger->error($message);
        $this->logger->error($traceAsString);
        $this->mailer->Subject = sprintf('ANSYSMONDAY %s Exception: %s', $identifier, $message);
        $text = sprintf('%s<br />%s<br />%s', $message, $code, $traceAsString);
        $this->mailer->Body = $text;
        $this->mailer->AltBody = $text;
        try {
            //$this->mailer->send();
        } catch (Exception $e) {
            $this->logger->error(sprintf('PHP-Mailer Error: %s', $e->getMessage()));
            $this->logger->error(sprintf('%s', $e->getTraceAsString()));
        }
        try {
            return $this->twig->render($response, 'error/error_default.twig', ['message' => $e->getMessage()]);
        } catch (LoaderError | SyntaxError | RuntimeError $e) {
            $this->logger->error(sprintf('PHP-Mailer Error: %s', $e->getMessage()));
            $this->logger->error(sprintf('%s', $e->getTraceAsString()));
        }
        return $response->withStatus(400);
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param array $errors
     * @return BaseAction
     */
    public function setErrors(array $errors): BaseAction
    {
        $this->errors = $errors;
        return $this;
    }

    protected function addError(string $message, string $severity, int $code = 400): BaseAction
    {
        $this->errors[] = [
            'message' => $message,
            'severity' => $severity,
            'code' => $code
        ];
        return $this;
    }

}
