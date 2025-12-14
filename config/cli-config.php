<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Setup;

require __DIR__ . '/../vendor/autoload.php';

$settings = include __DIR__ . '/settings.php';
$settings = $settings['doctrine'];

$config = Setup::createAnnotationMetadataConfiguration(
    $settings['meta']['entity_path'],
    $settings['meta']['auto_generate_proxies'],
    $settings['meta']['proxy_dir'],
    $settings['meta']['cache'],
    false
);

try {
    $em = EntityManager::create($settings['connection'], $config);
} catch (Exception $e) {
    throw $e;
}
try {
    $em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
} catch (Exception $e) {
    throw $e;
}

return ConsoleRunner::createHelperSet($em);
