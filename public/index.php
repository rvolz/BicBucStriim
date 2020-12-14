<?php

use App\Application\Handlers\HttpErrorHandler;
use App\Application\Handlers\ShutdownHandler;
use App\Domain\BicBucStriim\AppConstants;
use App\Domain\BicBucStriim\Configuration;
use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\ResponseEmitter;
require __DIR__ . '/../src/Application/Version.php';
if (!defined('APP_VERSION')) {
    define('APP_VERSION', 'unknown');
}
require __DIR__ . '/bbs-config.php';
if (!defined('BBS_BASE_PATH')) {
    define('BBS_BASE_PATH', '');
}
if (!defined('BBS_CALIBRE_PATH')) {
    define('BBS_CALIBRE_PATH', '');
}
if (!defined('BBS_LOG_LEVEL')) {
    define('BBS_LOG_LEVEL', 'info');
}
if (!defined('BBS_DEBUG_MODE')) {
    define('BBS_DEBUG_MODE', false);
}
if (!defined('BBS_IDLE_TIME')) {
    define('BBS_IDLE_TIME', 3600);
}

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}
require __DIR__ . '/../vendor/autoload.php';

// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

//if (BBS_DEBUG_MODE) {
//    $containerBuilder->enableCompilation(__DIR__ . '/../var/cache');
//}

// Set up settings
$settings = require __DIR__ . '/../app/settings.php';
$settings($containerBuilder, BBS_DEBUG_MODE, BBS_BASE_PATH, APP_VERSION, BBS_CALIBRE_PATH);


// Set up dependencies
$dependencies = require __DIR__ . '/../app/dependencies.php';
$dependencies($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();

// Instantiate the app
AppFactory::setContainer($container);
$app = AppFactory::create();
$callableResolver = $app->getCallableResolver();

// Register middleware
$middleware = require __DIR__ . '/../app/middleware.php';
$middleware($app);

// Register routes
$routes = require __DIR__ . '/../app/routes.php';
$routes($app);

/** @var bool $displayErrorDetails */
$displayErrorDetails = $container->get('settings')['displayErrorDetails'];

// Create Request object from globals
$serverRequestCreator = ServerRequestCreatorFactory::create();
$request = $serverRequestCreator->createServerRequestFromGlobals();

// Create Error Handler
$responseFactory = $app->getResponseFactory();
$errorHandler = new HttpErrorHandler($callableResolver, $responseFactory);

// Create Shutdown Handler
$shutdownHandler = new ShutdownHandler($request, $errorHandler, $displayErrorDetails);
register_shutdown_function($shutdownHandler);

// Add Routing Middleware
$app->addRoutingMiddleware();

// Add error handling middleware
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, BBS_DEBUG_MODE, BBS_DEBUG_MODE);
$errorMiddleware->setDefaultErrorHandler($errorHandler);

// Run App & Emit Response
$logger = $app->getContainer()->get(LoggerInterface::class);
$logger->info(
    $app->getContainer()->get(Configuration::class)[AppConstants::DISPLAY_APP_NAME] .
    ' ' .
    APP_VERSION);
$logger->info('Running on PHP: ' . PHP_VERSION);
if (BBS_DEBUG_MODE)
    $logger->info('DEBUG mode is enabled');

$app->setBasePath(BBS_BASE_PATH);

$response = $app->handle($request);
$responseEmitter = new ResponseEmitter();
$responseEmitter->emit($response);
