<?php

use App\ErrorRenderer\HtmlErrorRenderer;
use App\Factory\GqlClientFactory;
use App\Factory\LoggerFactory;
use App\Factory\SiteSettingsFactory;
use App\Service\MailService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Selective\BasePath\BasePathMiddleware;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Middleware\ErrorMiddleware;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Symfony\Component\Console\Application;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

return [
    'settings' => function () {
        return require __DIR__ . '/settings.php';
    },

    App::class => function (ContainerInterface $container) {
        AppFactory::setContainer($container);

        return AppFactory::create();
    },

    BasePathMiddleware::class => function (ContainerInterface $container) {
        return new BasePathMiddleware($container->get(App::class));
    },

    ErrorMiddleware::class => function (ContainerInterface $container) {
        $app = $container->get(App::class);
        $settings = $container->get('settings')['error'];
        $errorMiddleware = new ErrorMiddleware(
            $app->getCallableResolver(),
            $app->getResponseFactory(),
            (bool)$settings['display_error_details'],
            (bool)$settings['log_errors'],
            (bool)$settings['log_error_details']
        );
        $defaultErrorHandler = $errorMiddleware->getDefaultErrorHandler();
        $defaultErrorHandler->registerErrorRenderer('text/html', HtmlErrorRenderer::class);
        return $errorMiddleware;
    },

    LoggerFactory::class => function (ContainerInterface $container) {
        return new LoggerFactory($container->get('settings')['logger']);
    },

    GqlClientFactory::class => function (ContainerInterface $container) {
        $settings = $container->get('settings');
        return new GqlClientFactory($settings['mondayclient']['url'], $settings['mondayclient']['token']);
    },

    Twig::class => function (ContainerInterface $container) {
        $settings = $container->get('settings');
        $twigSettings = $settings['twig'];

        $options = $twigSettings['options'];
        $options['cache'] = $options['cache_enabled'] ? $options['cache_path'] : false;

        $twig = Twig::create($twigSettings['paths'], $options);

        return $twig;
    },

    MailService::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['phpmailer'];
        return new MailService($settings);
    },

    SiteSettingsFactory::class => function (ContainerInterface $container) {
        $settings = $container->get('settings');
        return new SiteSettingsFactory($settings['site']);
    },

    TwigMiddleware::class => function (ContainerInterface $container) {
        return TwigMiddleware::createFromContainer(
            $container->get(App::class),
            Twig::class
        );
    },

    ResponseFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(App::class)->getResponseFactory();
    },

    Session::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['session'];
        if (PHP_SAPI === 'cli') {
            return new Session(new MockArraySessionStorage());
        } else {
            return new Session(new NativeSessionStorage($settings));
        }
    },

    SessionInterface::class => function (ContainerInterface $container) {
        return $container->get(Session::class);
    },

    EntityManager::class => function (ContainerInterface $container) {
        $settings = $container->get('settings');
        $config = Setup::createAnnotationMetadataConfiguration(
            $settings['doctrine']['meta']['entity_path'],
            $settings['doctrine']['meta']['auto_generate_proxies'],
            $settings['doctrine']['meta']['proxy_dir'],
            $settings['doctrine']['meta']['cache'],
            false
        );
        $em = EntityManager::create($settings['doctrine']['connection'], $config);
        $em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
        return $em;
    },

    Application::class => function (ContainerInterface $container) {
        $application = new Application();

        foreach ($container->get('settings')['commands'] as $class) {
            $application->add($container->get($class));
        }

        return $application;
    },

];
