<?php

return [
    'settings' => [
        'debug' => true,

        // Display call stack in orignal slim error when debug is off
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header
        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],
        // Monolog settings
        'logger' => [
            'name' => 'BicBucStriim',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        // BicBucStriim settings
        'bbs' => [
            'dataDb' => __DIR__ . '/../data/data.db',
            'public' => __DIR__ . '/../public',
            // TODO get version from outside
            'version' => '2.0.0-alpha.1',
            'langs' => array('de', 'en', 'es', 'fr', 'gl', 'hu', 'it', 'nl', 'pl'),
        ],
    ],
];