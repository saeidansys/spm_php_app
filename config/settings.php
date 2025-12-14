<?php

use App\Console\ExampleCommand;
use App\Service\MailService;
use Monolog\Logger;

// Should be set to 0 in production
error_reporting(1); 

// Should be set to '0' in production
ini_set('display_errors', '1');

// set max upload size to 50M
ini_set('upload_max_filesize', "50M");

// Timezone
//date_default_timezone_set('Europe/Berlin');
date_default_timezone_set('America/Chicago');

// Settings
$settings = [];

// Path settings
$settings['root'] = dirname(__DIR__);
$settings['temp'] = $settings['root'] . '/tmp';
$settings['public'] = $settings['root'] . '/public';

// Error Handling Middleware settings
$settings['error'] = [

    // Should be set to false in production
    'display_error_details' => true,

    // Parameter is passed to the default ErrorHandler
    // View in rendered output by enabling the "displayErrorDetails" setting.
    // For the console and unit tests we also disable it
    'log_errors' => true,

    // Display error details in error log
    'log_error_details' => true,
];

$settings['logger'] = [
    'name' => 'app',
    'path' => __DIR__ . '/../logs',
    'filename' => 'app.log',
    'level' => Logger::DEBUG,
    'file_permission' => 0775,
];

$settings['twig'] = [
    // Template paths
    'paths' => [
        __DIR__ . '/../templates',
    ],
    // Twig environment options
    'options' => [
        // Should be set to true in production
        'cache_enabled' => false,
        'cache_path' => __DIR__ . '/../tmp/twig',
    ],
];

$settings['mondayclient'] = [
    'url' => 'https://api.monday.com/v2',
    'token' => getenv('mondayDOTcomtoken'),
];

$settings['site'] = [
    'host' => 'https://ansys.monday.com',
    'service' => 'https://monday-pm-tool.ansys.com/',
    'token' => getenv('mondayDOTcomtoken'),
];

$settings['session'] = [
    'name' => 'mondayhooks',
    'cache_expire' => 0,
];

$settings['commands'] = [
    ExampleCommand::class,
    // Add more here...
];

// mailer config
$settings['phpmailer'] = [
    MailService::EXCEPTIONS => true,
    'noMailings' => true,
    'isSmtp' => true,
    'defaults' => [
        'From' => 'mail@domain.com',
        'FromName' => 'mondayhooks',
        'isHtml' => true,
        'errorAddress' => 'mail@domain.com',
        'errorName' => 'mondayhooks-ansys-error',
        'errorSubject' => 'mondayhooks - ansys error',
        'defaultCc' => null,
        'defaultBcc' => null,
        'smtpData' => [
            'smtpDebug' => 2,
            'smtpAuth' => true,
            'smtpSecure' => 'tls',
            'mainMailer' => 'mailer.ansys.com',
            'backupMailer' => 'mailer2.ansys.com',
            'username' => 'username',
            'password' => 'XXXXX',
            'smtpPort' => 587,
        ],
    ],
    'mailTexts' => []
];

$settings['doctrine'] = [
    'meta' => [
        'entity_path' => [
            'src/Entity',
        ],
        'auto_generate_proxies' => true,
        'proxy_dir' =>  __DIR__.'/../tmp/doctrine',
        'cache' => null,
    ],
    'connection' => [

        'driver'   => 'pdo_mysql',
        'host'     => 'localhost',
        'dbname'   => 'monday_pm_tool',
        'user'     => 'sp_monday',
        'password' => getenv('MYSQL_PASSWORD'),
        'charset'  => 'utf8',

        'driverOptions' => [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]

    ]
];

return $settings;
