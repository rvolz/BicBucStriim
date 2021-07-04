<?php

use Alchemy\Zippy\Zippy;

// Require Composer's autoloader
require __DIR__ . '/../vendor/autoload.php';

// Load Zippy
$zippy = Zippy::load();
$archive = $zippy->create('bicbucstriim.zip', array(
    'img' => 'img',
    'js' => 'js',
    'style/style.css' => 'style/style.css',
    'style/jquery' => 'style/jquery',
    'lib/BicBucStriim' => 'lib/BicBucStriim',
    'vendor/autoload.php' => 'vendor/autoload.php',
    'vendor/DateTimeFileWriter.php' => 'vendor/DateTimeFileWriter.php',
    'vendor/epub.php' => 'vendor/epub.php',
    'vendor/rb.php' => 'vendor/rb.php',
    'vendor/aura' => 'vendor/aura',
    'vendor/composer' => 'vendor/composer',
    'vendor/slim' => 'vendor/slim',
    'vendor/twig' => 'vendor/twig',
    'vendor/swiftmailer' => 'vendor/swiftmailer',
    'vendor/ircmaxell' => 'vendor/ircmaxell',
    'vendor/dflydev/markdown' => 'vendor/dflydev/markdown',
    'vendor/symfony' => 'vendor/symfony',
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
), true);
