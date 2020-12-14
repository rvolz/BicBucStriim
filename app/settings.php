<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder, bool $debugMode, string $basePath, string $version, string $calibrePath) {
    // Global Settings Object
    $containerBuilder->addDefinitions([
        'settings' => [
            // mode
            'debug' => $debugMode,

            // If not installed at root, enter the path to the installation here
            'basePath' => $basePath,

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
                'path' => getenv('docker') ? 'php://stdout' : __DIR__ . '/../var/logs/app.log',
                'level' => Logger::DEBUG, // TODO change according to bbs-config.php
            ],

            // BicBucStriim settings
            'bbs' => [
                'dataDb' => __DIR__ . '/../data/data.db',
                'public' => __DIR__ . '/../public',
                'version' => $version,
                'langs' => array('de', 'en', 'es', 'fr', 'gl', 'hu', 'it', 'nl', 'pl'),
            ],

            // Calibre settings
            'calibre' => [
               'libraryPath' => $calibrePath,
            ],
        ],
    ]);
};
