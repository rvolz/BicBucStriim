<?php

require __DIR__ . '/../vendor/autoload.php';
use App\Application\Handlers\HttpErrorHandler;
use App\Application\Handlers\ShutdownHandler;
use App\Domain\BicBucStriim\AppConstants;
use App\Domain\BicBucStriim\Configuration;
use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\ResponseEmitter;

ini_set('session.gc_maxlifetime', 3600);

// Load a .env file if available in the public directory.
// Note: existing environment variables will not be overwritten by this call
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->ifPresent('docker')->isBoolean();
$dotenv->ifPresent('BBS_DEBUG_MODE')->isBoolean();
$dotenv->ifPresent('BBS_IDLE_TIME')->isInteger();
$dotenv->load();

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

// Set up settings
$settings = require __DIR__ . '/../app/settings.php';
$settings($containerBuilder);

/** @var bool $debugMode */
$debugMode = $_ENV['BBS_DEBUG_MODE'] ?? false;
if (!$debugMode) {
    $containerBuilder->enableCompilation(__DIR__ . '/../var/cache');
}

// Set up dependencies
$dependencies = require __DIR__ . '/../app/dependencies.php';
$dependencies($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();

/** @var bool $displayErrorDetails */
$displayErrorDetails = $container->get('settings')['displayErrorDetails'];
/** @var string $appVersion */
$appVersion = $container->get('settings')['bbs']['version'];
/** @var string $basePath */
$basePath = $container->get('settings')['basePath'];

// Instantiate the app
AppFactory::setContainer($container);
$app = AppFactory::create();

$app->setBasePath($basePath);

$callableResolver = $app->getCallableResolver();

// Register middleware
$middleware = require __DIR__ . '/../app/middleware.php';
$middleware($app);

// Register routes
$routes = require __DIR__ . '/../app/routes.php';
$routes($app);

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
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, $debugMode, $debugMode);
$errorMiddleware->setDefaultErrorHandler($errorHandler);

// Run App & Emit Response
$logger = $app->getContainer()->get(LoggerInterface::class);
$logger->info(
    $app->getContainer()->get(Configuration::class)[AppConstants::DISPLAY_APP_NAME] .
    ' ' .
    $appVersion
);
$logger->info('Running on PHP: ' . PHP_VERSION);
if ($debugMode) {
    $logger->info('DEBUG mode is enabled');
}

$response = $app->handle($request);
$responseEmitter = new ResponseEmitter();
$responseEmitter->emit($response);
