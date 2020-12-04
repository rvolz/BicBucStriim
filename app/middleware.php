<?php

declare(strict_types=1);

use App\Application\Middleware\SessionMiddleware;
use App\Application\Middleware\CalibreConfigMiddleware;
use App\Application\Middleware\OwnConfigMiddleware;
use App\Application\Middleware\NegotiationMiddleware;
use App\Application\Middleware\RequestLogMiddleware;

use Slim\App;

return function (App $app) {
    $logger = $app->getContainer()->get('logger');
    $config = $app->getContainer()->get('config');
    $settings = $app->getContainer()->get('settings');
    $bbs = $app->getContainer()->get('bbs');
    $calibre = $app->getContainer()->get('calibre');
    $l10n = $app->getContainer()->get('l10n');

    $app->add(SessionMiddleware::class); // TODO conflict with aura?
    $app->add(new CalibreConfigMiddleware($logger, $calibre, $config));
    $app->add(new OwnConfigMiddleware($logger, $bbs, $config));
    // NOTE supply argument trusted proxies, if necessary?
    //$app->add(new \RKA\Middleware\ProxyDetection());
    // NOTE only JSON and OPDS requests will be accepted
    $app->add(new NegotiationMiddleware($logger, $settings['bbs']['langs'], $l10n));
    $app->add(new RequestLogMiddleware($logger));
    // TODO enable caching
    //$app->add(new \Slim\HttpCache\Cache('public', 86400));
    $app->add(Slim\Views\TwigMiddleware::createFromContainer($app));
};
