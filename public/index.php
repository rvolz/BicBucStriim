<?php
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
// TODO enable for production
//session_start();
// Instantiate the app
$settings = require __DIR__ . '/../app/settings.php';
$app = new \Slim\App($settings);
// Set up dependencies
require __DIR__ . '/../app/dependencies.php';
// Register middleware
require __DIR__ . '/../app/middleware.php';
// Route helpers
require __DIR__ . '/../app/helpers.php';
// Register routes
//require __DIR__ . '/../app/routes/admin.php';
require __DIR__ . '/../app/routes/static.php';
require __DIR__ . '/../app/routes/opds.php';
// Run app
$logger = $app->getContainer()->get('logger');
$logger->info(
    $app->getContainer()->get(\BicBucStriim\AppConstants::DISPLAY_APP_NAME) .
    ' ' .
    $app->getContainer()->get('settings')['version']);
$logger->info('Running on PHP: ' . PHP_VERSION);
// TODO Output env: prod, debug, dev
$app->run();
