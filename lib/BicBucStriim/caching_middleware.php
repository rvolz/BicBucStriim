<?php
/**
 * BicBucStriim
 *
 * Copyright 2012-2014 Rainer Volz
 * Licensed under MIT License, see LICENSE
 * 
 */ 

require 'vendor/autoload.php';

class CachingMiddleware extends \Slim\Middleware {

    protected $resources;

    /**
     * Initialize the configuration
     *
     * @param array $config an array of resource strings
     */
    public function __construct($config) {
        $this->resources = $config;
    }
    /**
     * If the current resource belongs to the admin area caching will be disabled.
     *
     * This call must happen before own_config_middleware, because there the PHP 
     * session will be started, and cache-control must happen before that.
     */
    public function call() {
        $app = $this->app;
        $request = $app->request;
        $resource = $request->getResourceUri();
        foreach ($this->resources as $noCacheResource) {
            if (Utilities::stringStartsWith($resource, $noCacheResource)) {
                session_cache_limiter('nocache');
                $app->getLog()->debug('caching_middleware: caching disabled for '.$resource); 
                break;
            }
        }
        $this->next->call();
    }

}
?>
