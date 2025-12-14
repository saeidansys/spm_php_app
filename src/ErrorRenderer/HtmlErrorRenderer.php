<?php

namespace App\ErrorRenderer;

use Exception;
use Slim\Error\AbstractErrorRenderer;
use Slim\Exception\HttpException;
use Slim\Views\Twig;
use Throwable;

class HtmlErrorRenderer extends AbstractErrorRenderer
{

    /**
     * @var string
     */
    protected string $defaultErrorTitle = 'An error occured...';

    private Twig $twig;

    public function __construct(Twig $twig)
    {
        $this->twig = $twig;
    }

    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
        if ($displayErrorDetails) {
            $html = '<p>Unable to process the request, the following error occured:</p>';
            $html .= $this->renderExceptionFragment($exception);
        } else {
            $html = "<p>{$this->getErrorDescription($exception)}</p>";
        }
        return $this->twig->fetch('error/error_default.twig', ['title' => $this->getErrorTitle($exception), 'html' => $html]);
    }

    /**
     * @param Throwable $exception
     * @return string
     */
    private function renderExceptionFragment(Throwable $exception): string
    {
        $html = sprintf('<div><strong>Type:</strong> %s</div>', get_class($exception));

        $code = $exception->getCode();
        if ($code !== null) {
            $html .= sprintf('<div><strong>Code:</strong> %s</div>', $code);
        }

        $message = $exception->getMessage();
        if ($message !== null) {
            $html .= sprintf('<div><strong>Message:</strong> %s</div>', htmlentities($message));
        }

        $file = $exception->getFile();
        if ($file !== null) {
            $html .= sprintf('<div><strong>File:</strong> %s</div>', $file);
        }

        $line = $exception->getLine();
        if ($line !== null) {
            $html .= sprintf('<div><strong>Line:</strong> %s</div>', $line);
        }

        $trace = $exception->getTraceAsString();
        if ($trace !== null) {
            $html .= '<h2>Trace</h2>';
            $html .= sprintf('<pre>%s</pre>', htmlentities($trace));
        }

        return $html;
    }

    protected function getErrorDescription(Throwable $exception): string
    {
        if ($exception instanceof HttpException) {
            return $exception->getDescription();
        }
        if ($exception instanceof Exception) {
            return $exception->getMessage();
        }
        return $exception->getMessage();
    }

}
