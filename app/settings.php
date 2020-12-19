<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Monolog\Logger;
require __DIR__ . '/../src/Application/Version.php';

return function (ContainerBuilder $containerBuilder) {
    /** @var bool $docker; */
    $docker = getenv('docker') ? true : false;
    $debugMode = isset($_ENV['BBS_DEBUG_MODE']) ? $_ENV['BBS_DEBUG_MODE'] : false;
    $basePath = isset($_ENV['BBS_BASE_PATH']) ? $_ENV['BBS_BASE_PATH'] : '';
    $logLevel = isset($_ENV['BBS_LOG_LEVEL']) ? $_ENV['BBS_LOG_LEVEL'] : 'info';
    switch ($logLevel) {
        case 'debug':
            $realLogLevel = Logger::DEBUG;
            break;
        case 'info':
            $realLogLevel = Logger::INFO;
            break;
        case 'warning':
            $realLogLevel = Logger::WARNING;
            break;
        case 'error':
            $realLogLevel = Logger::ERROR;
            break;
        default:
            $realLogLevel = Logger::INFO;
    }
    if ($debugMode)
        $realLogLevel = Logger::DEBUG;
    /** @var int $idleTime; */
    $idleTime = (int) $_ENV['BBS_IDLE_TIME'];

    // Global Settings Object
    $containerBuilder->addDefinitions([

        'settings' => [
            // mode
            'debug' => $debugMode,

            // If not installed at root, enter the path to the installation here
            'basePath' => $basePath,

            // mode
            'idleTime' => !empty($idleTime)  ? $idleTime : 3600,

            // path to the 'public' directory
            'publicPath' => __DIR__ . '/../public/',

            // Display call stack in orignal slim error when debug is off
            'displayErrorDetails' => $debugMode,

            // TODO Allow the web server to send the content-length header?
            'addContentLengthHeader' => false,

            // Renderer settings
            'renderer' => [
                'template_path' => __DIR__ . '/../templates/',
                'cache_path' => __DIR__ . '/../var/cache',
            ],

            // Monolog settings
            'logger' => [
                'name' => 'BicBucStriim',
                'path' => $docker ? 'php://stdout' : __DIR__ . '/../var/logs/app.log',
                'level' => $realLogLevel,
            ],

            // BicBucStriim settings
            'bbs' => [
                'dataDb' => __DIR__ . '/../data/data.db',
                'public' => __DIR__ . '/../public',
                'version' => APP_VERSION,
                'langs' => array('de', 'en', 'es', 'fr', 'gl', 'hu', 'it', 'nl', 'pl'),
            ]
        ],
    ]);
};
