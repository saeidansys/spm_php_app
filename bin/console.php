<?php

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;

require_once __DIR__ . '/../vendor/autoload.php';

/** @var ContainerInterface $container */
$container = (require __DIR__ . '/../config/bootstrap.php')->getContainer();

$application = $container->get(Application::class);
$application->run();
