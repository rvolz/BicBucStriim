<?php

declare(strict_types=1);

use App\Domain\BicBucStriim\BicBucStriim;
use App\Domain\BicBucStriim\AppConstants;
use App\Domain\BicBucStriim\BicBucStriimRepository;
use App\Domain\BicBucStriim\Configuration;
use \App\Domain\BicBucStriim\L10n;
use App\Domain\Calibre\Calibre;
use App\Domain\Calibre\CalibreRepository;
use App\Domain\User\User;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([

        // monolog
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get('settings')['logger'];
            $logger = new Monolog\Logger($settings['name']);
            $logger->pushProcessor(new Monolog\Processor\UidProcessor());
            $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
            return $logger;
        },

        // Twig
        Twig::class => function (ContainerInterface $c) {
            $settings = $c->get('settings');
            $templates = $settings['renderer']['template_path'];
            $cache = $settings['renderer']['cache_path'];
            return Twig::create($templates, ['cache' => $cache]);
        },

        // BicBucStriim
        BicBucStriimRepository::class => function (ContainerInterface $c) {
            $settings = $c->get('settings')['bbs'];
            $logger = $c->get(LoggerInterface::class);
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
        Configuration::class => function (ContainerInterface $c) {
            return new Configuration($c->get(LoggerInterface::class), $c->get(BicBucStriimRepository::class));
        },

        // Calibre
        CalibreRepository::class => function (ContainerInterface $c) {
            $cdir = $c->get(Configuration::class)[AppConstants::CALIBRE_DIR];
            // $cdir = $c->get('settings')['calibre']['libraryPath'];
            $logger = $c->get(LoggerInterface::class);
            $logger->info("Calibre library dir: {$cdir}");
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
                    //$calibre = Calibre:;
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
        L10n::class => function (ContainerInterface $c) {
            return new L10n();
        },

        // User
        User::class => function (ContainerInterface $c) {
            return User::emptyUser();
        },
    ]);
};

