<?php

declare(strict_types=1);

use App\Application\Middleware\AuthMiddleware;
use App\Application\Middleware\CalibreConfigMiddleware;
use App\Application\Middleware\OwnConfigMiddleware;
use App\Application\Middleware\NegotiationMiddleware;
use App\Application\Middleware\RequestLogMiddleware;

use App\Domain\BicBucStriim\BicBucStriimRepository;
use App\Domain\BicBucStriim\Configuration;
use App\Domain\Calibre\CalibreRepository;
use \App\Domain\BicBucStriim\L10n;
use App\Domain\User\UserLanguage;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\HttpCache\Cache;
use Slim\Views\Twig;

return function (App $app) {
    /* @var LoggerInterface $logger */
    $logger = $app->getContainer()->get(LoggerInterface::class);
    /* @var Configuration $config */
    $config = $app->getContainer()->get(Configuration::class);
    /* @var array $settings */
    $settings = $app->getContainer()->get('settings');
    /* @var BicBucStriimRepository $bbs */
    $bbs = $app->getContainer()->get(BicBucStriimRepository::class);
    /* @var CalibreRepository $calibre */
    $calibre = $app->getContainer()->get(CalibreRepository::class);
    /* @var L10n $l10n */
    $l10n = $app->getContainer()->get(L10n::class);

    $app->getBasePath();

    $app->add(new CalibreConfigMiddleware($logger, $calibre, $config));
    $app->add(new OwnConfigMiddleware($logger, $bbs, $config));
    // NOTE supply argument trusted proxies, if necessary?
    //$app->add(new \RKA\Middleware\ProxyDetection());
    // NOTE only JSON and OPDS requests will be accepted
    $app->add(new NegotiationMiddleware($logger, new UserLanguage(), $l10n));
    $app->add(new RequestLogMiddleware($logger));
    $app->add(new Cache('public', 86400));
    $app->add(Slim\Views\TwigMiddleware::createFromContainer($app, Twig::class));
    $app->add(new AuthMiddleware($logger, $bbs->getDb(), $app->getContainer()));
    $app->add(new Middlewares\TrailingSlash(true));
};
