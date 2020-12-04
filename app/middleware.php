<?php
// Application middleware
// e.g: $app->add(new \Slim\Csrf\Guard);

$app->add(new App\Application\Middleware\CalibreConfigMiddleware($container['logger'], $container['calibre'], $container['config']));
$app->add(new App\Application\Middleware\OwnConfigMiddleware($container['logger'], $container['bbs'], $container['config']));
// NOTE supply argument trusted proxies, if necessary?
//$app->add(new \RKA\Middleware\ProxyDetection());
// NOTE only JSON and OPDS requests will be accepted
$app->add(new App\Application\Middleware\NegotiationMiddleware($container['logger'], $container['settings']['bbs']['langs'], $container['l10n']));
$app->add(new App\Application\Middleware\RequestLogMiddleware($container['logger']));
// TODO enable caching
//$app->add(new \Slim\HttpCache\Cache('public', 86400));
$app->add(Slim\Views\TwigMiddleware::createFromContainer($app));
