<?php
/**
 * BicBucStriim
 *
 * Copyright 2012-2014 Rainer Volz
 * Licensed under MIT License, see LICENSE
 * 
 */ 

require 'vendor/autoload.php';
require_once 'lib/BicBucStriim/bicbucstriim.php';
require_once 'lib/BicBucStriim/session_factory.php';
require_once 'lib/BicBucStriim/segment_factory.php';
require_once 'lib/BicBucStriim/session.php';
use Aura\Auth;

class LoginMiddleware extends \Slim\Middleware {

    protected $realm;
    protected $static_resource_paths;

    /**
     * Initialize the PDO connection and merge user
     * config with defaults.
     *
     * @param array $config
     */
    public function __construct($realm, $statics) {
        $app = $this->app;
        $this->realm = $realm;  
        $this->static_resource_paths = $statics;
    }


    public function call() {
        $this->app->hook('slim.before.dispatch', array($this, 'authBeforeDispatch'));
        $this->next->call();
    }

    public function authBeforeDispatch() {
        global $globalSettings;

        $app = $this->app;
        $request = $app->request;
        $resource = $request->getResourceUri();
        $accept = $request->headers('ACCEPT');
        $app->getLog()->debug('login resource: '.$resource);
        if ($globalSettings[LOGIN_REQUIRED] == 1) {
            if (!$this->is_static_resource($resource) && !$this->is_authorized()) {
                if ($resource === '/login/') {
                    // special case login page
                    $app->getLog()->debug('login: login page authorized');
                    return;    
                } elseif (stripos($resource, '/opds') === 0) {
                    $app->getLog()->debug('login: unauthorized OPDS request');
                    $app->response->headers->set('WWW-Authenticate', sprintf('Basic realm="%s"', $this->realm));
                    $app->halt(401,'Please authenticate');
                } elseif ($app->request->getMethod() != 'GET' && ($app->request->isXhr() || $app->request->isAjax())) {
                    $app->getLog()->debug('login: unauthorized JSON request');
                    $app->response->headers->set('WWW-Authenticate', sprintf('Basic realm="%s"', $this->realm));
                    $app->halt(401,'Please authenticate');
                } else {
                    $app->getLog()->debug('login: redirecting to login');
                    // now we can also use the native app->redirect method!
                    $this->app->redirect($app->request->getRootUri().'/login/');
                }
            }
        } else {
            if ($resource === '/login/') {
                $this->is_authorized();
                // special case login page
                $app->getLog()->debug('login: login page authorized');
                return;    
            } elseif (stripos($resource, '/admin') === 0 && !$this->is_static_resource($resource) && !$this->is_authorized()) {
                $app->getLog()->debug('login: redirecting to login');
                $this->app->redirect($app->request->getRootUri().'/login/');
            }
        }
    }

    /**
     * Static resources must not be protected. Return true id the requested resource 
     * belongs to a static resource path, else false.
     */
    protected function is_static_resource($resource) {
        $path_parts = preg_split('/\//', $resource);
        if (isset($path_parts)) {
            # Some OPDS clients like Aldiko don't send auth information for image resources so we have to handle them here
            # FIXME better solution for resources
            if (sizeof($path_parts) == 5 && ($path_parts[3] == 'cover' || $path_parts[3] == 'thumbnail')) {
                return true;
            }
            foreach ($this->static_resource_paths as $static_resource_path) {
                if (strcasecmp($static_resource_path, $path_parts[1]) === 0)
                    return true;
            }    
        }
        return false;
    }

    /**
     * Check if the access request is authorized by a user. A request must either contain session data from 
     * a previous login or contain a HTTP Basic authorization info, which is then used to
     * perform a login against the users table in the database. 
     * @return true if authorized else false
     */
    protected function is_authorized() {
        $app = $this->app;
        $req = $app->request;
        $session_factory = new \BicBucStriim\SessionFactory();
        $session = $session_factory->newInstance($_COOKIE);
        $session->setCookieParams(array('path' => $app->request->getRootUri() . '/'));
        $auth_factory = new \Aura\Auth\AuthFactory($_COOKIE, $session);
        $app->auth = $auth_factory->newInstance();
        $hash = new \Aura\Auth\Verifier\PasswordVerifier(PASSWORD_BCRYPT);
        $cols = array('username', 'password', 'id', 'email', 'role', 'languages', 'tags');
        $pdo_adapter = $auth_factory->newPdoAdapter($app->bbs->mydb, $hash, $cols, 'user');
        $app->login_service = $auth_factory->newLoginService($pdo_adapter);
        $app->logout_service = $auth_factory->newLogoutService($pdo_adapter);
        $resume_service = $auth_factory->newResumeService($pdo_adapter);
        try {
            $resume_service->resume($app->auth);
        } catch(ErrorException $e) {
            $app->getLog()->warn('login error: bad cookie data '.var_export(get_class($e),true));
        }
        $app->getLog()->debug("after resume: " . $app->auth->getStatus());
        if ($app->auth->isValid()) {
            // already logged in -- check for bad cookie contents
            $ud = $app->auth->getUserData();
            if (is_array($ud) && array_key_exists('role', $ud) && array_key_exists('id', $ud)) {
                // contents seems ok
                return true;
            } else {
                $app->getLog()->warn("bad cookie contents: killing session");
                // bad cookie contents, kill it
                $session->destroy();
                return false;
            }
        } else {
            // not logged in - check for login info
            $auth = $this->checkPhpAuth($req);
            if (is_null($auth))
                $auth = $this->checkHttpAuth($req);
            $app->getLog()->debug('login auth: '.var_export($auth,true));
            // if auth info found check the database
            if (is_null($auth))
                return false; 
            else {
                try {
                    $app->login_service->login($app->auth, array(
                        'username' => $auth[0],
                        'password' => $auth[1]));
                    $app->getLog()->debug('login status: '.var_export($app->auth->getStatus(),true));
                } catch (Auth\Exception $e) {
                    $app->getLog()->debug('login error: '.var_export(get_class($e),true));
                }
                return $app->auth->isValid();
            }
        }
    }

    /**
     * Look for PHP authorization headers
     * @param $request HTTP request
     * @return array with username and pasword, or null
     */
    protected function checkPhpAuth($request) {
        $authUser = $request->headers('PHP_AUTH_USER');
        $authPass = $request->headers('PHP_AUTH_PW');  
        if (!empty($authUser) && !empty($authPass))
            return array($authUser, $authPass);
        else
            return null;
    }

    /**
     * Look for a HTTP Authorization header and decode it
     * @param $request HTTP request
     * @return array with username and pasword, or null
     */
    protected function checkHttpAuth($request) {
        $b64auth = $request->headers('Authorization');
        if (!empty($b64auth)) {
            $auth_array1 = preg_split('/ /', $b64auth);
            if (!isset($auth_array1) || strcasecmp('Basic', $auth_array1[0]) != 0)
                return null;
            if (sizeof($auth_array1) != 2 || !isset($auth_array1[1]))
                return null;
            $auth = base64_decode($auth_array1[1]);
            return preg_split('/:/', $auth);
        } else
            return null;
    }

}
?>
