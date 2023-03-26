<?php

use Alchemy\Zippy\Zippy;

// Require Composer's autoloader
require __DIR__ . '/../vendor/autoload.php';

// Load Zippy
$zippy = Zippy::load();
$archive = $zippy->create('bicbucstriim.zip', [
    'img' => 'img',
    'js' => 'js',
    'style/style.css' => 'style/style.css',
    'style/jquery' => 'style/jquery',
    'lib/BicBucStriim' => 'lib/BicBucStriim',
    'vendor' => 'vendor',
    /**
    'vendor/autoload.php' => 'vendor/autoload.php',
    'vendor/DateTimeFileWriter.php' => 'vendor/DateTimeFileWriter.php',
    'vendor/epub.php' => 'vendor/epub.php',
    'vendor/rb.php' => 'vendor/rb.php',
    'vendor/aura/auth' => 'vendor/aura/auth',
    'vendor/aura/session' => 'vendor/aura/session',
    'vendor/composer' => 'vendor/composer',
    'vendor/dflydev/markdown' => 'vendor/dflydev/markdown',
    'vendor/doctrine/deprecations' => 'vendor/doctrine/deprecations',
    'vendor/doctrine/lexer' => 'vendor/doctrine/lexer',
    'vendor/egulias/email-validator' => 'vendor/egulias/email-validator',
    'vendor/gabordemooij/redbean' => 'vendor/gabordemooij/redbean',
    'vendor/ircmaxell/password-compat' => 'vendor/ircmaxell/password-compat',
    'vendor/slim/slim' => 'vendor/slim/slim',
    'vendor/slim/views' => 'vendor/slim/views',
    'vendor/swiftmailer/swiftmailer' => 'vendor/swiftmailer/swiftmailer',
    'vendor/symfony' => 'vendor/symfony',
    'vendor/twig/twig' => 'vendor/twig/twig',
     */
    'templates' => 'templates',
    'data' => 'data',
    'index.php',
    'installcheck.php',
    'favicon.ico',
    'bbs-icon.png',
    'CHANGELOG.md',
    '.htaccess' => '.htaccess',
    'NOTICE',
    'LICENSE',
    'README.md',
], true);
