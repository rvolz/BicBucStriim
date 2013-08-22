<?php
session_start();
date_default_timezone_set('UTC');

$vendorPos = strpos(__DIR__, 'vendor/slim/strong');
if($vendorPos !== false) {
    // Package has been cloned within another composer package, resolve path to autoloader
    $vendorDir = substr(__DIR__, 0, $vendorPos) . 'vendor/';
    require $vendorDir . 'autoload.php';
} else {
    // Package itself (cloned standalone)
    require __DIR__.'/../vendor/autoload.php';
}

require 'tests/Strong/Provider/ProviderTesting.php';
require 'tests/Mock/PDOMock.php';
require 'tests/Mock/StmtMock.php';
require 'tests/Mock/ProviderMock.php';
require 'tests/Mock/ProviderAbstractMock.php';
require 'tests/Mock/ProviderInvalid.php';
require 'tests/Mock/UserActiverecordMock.php';