<?php

declare(strict_types=1);

use App\Domain\BicBucStriim\BicBucStriim;
use App\Domain\BicBucStriim\AppConstants;
use App\Domain\BicBucStriim\Configuration;
use App\Domain\Calibre\Calibre;
use App\Infrastructure\Mail\Mailer;
//use Slim\HttpCache\CacheProvider;
use Slim\Views\Twig;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([

        // monolog
        'logger' => function (ContainerInterface $c) {
            $settings = $c->get('settings')['logger'];
            $logger = new Monolog\Logger($settings['name']);
            $logger->pushProcessor(new Monolog\Processor\UidProcessor());
            $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
            return $logger;
        },

        // Twig
        'view' => function (ContainerInterface $c) {
            // TODO provide dir var/cache
            $settings = $c->get('settings');
            $templates = $settings['renderer']['template_path'];
            $cache = $settings['renderer']['cache_path'];
            return Twig::create($templates, ['cache' => $cache]);
        },

        // BicBucStriim
        'bbs' => function (ContainerInterface $c) {
            $settings = $c->get('settings')['bbs'];
            $logger = $c->get('logger');
            $dbd = $settings['dataDb'];
            $public = $settings['public'];
            $logger->debug("using bbs db $dbd and public path $public");
            $bbs = new BicBucStriim($dbd, true);
            if (!$bbs->dbOk()) {
                $bbs->createDataDb($dbd);
                $bbs = new BicBucStriim($dbd, true);
            }
            return $bbs;
        },

        // Application configuration
        'config' => function (ContainerInterface $c) {
            return new Configuration($c->get('logger'), $c->get('bbs'));
        },

        // Calibre
        'calibre' => function (ContainerInterface $c) {
            $cdir = $c->get('config')[AppConstants::CALIBRE_DIR];
            $logger = $c->get('logger');
            if (!empty($cdir)) {
                try {
                    $calibre = new Calibre($cdir . '/metadata.db');
                } catch (PDOException $ex) {
                    $logger->error("Error opening Calibre library: " . var_export($ex, true));
                    return null;
                }
                if ($calibre->libraryOk()) {
                    $logger->debug('Calibre library ok');
                } else {
                    $calibre = null;
                    $logger->error(getcwd());
                    $logger->error("Unable to open Calibre library at " . realpath($cdir));
                }
            } else {
                $logger->info('No Calibre library defined yet');
                $calibre = null;
            }
            return $calibre;
        },

        // l10n
        'l10n' => function (ContainerInterface $c) {
            return new \App\Domain\BicBucStriim\L10n();
        }
    ]);
};

