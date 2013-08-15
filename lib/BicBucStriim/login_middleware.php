<?php

require 'vendor/autoload.php';
require_once 'lib/BicBucStriim/bicbucstriim.php';
use Strong\Strong;

class LoginMiddleware extends \Slim\Middleware {

    var $pdo;
    var $realm;
    var $static_resource_paths;

    /**
     * Initialize the PDO connection and merge user
     * config with defaults.
     *
     * @param array $config
     */
    public function __construct($realm, $statics) {
        $this->realm = $realm;  
        $this->static_resource_paths = $statics;
    }

    public function call() {
        $app = $this->app;
        $request = $app->request;
        $resource = $request->getResourceUri();
        $app->getLog()->debug('login: resource '.$resource);
        if ($this->is_static_resource($resource) || $this->is_authorized()) {
            $app->getLog()->debug('login: access authorized');
            $this->next->call();
        } elseif ($resource === '/login/') {
            $app->getLog()->debug('login: login page authorized');
            $this->next->call();
        } else {
            $app->getLog()->debug('login: access not authorized');
            if ($request->getMediaType() === 'application/json') {
                $app->response['WWW-Authenticate'] = 'Basic realm="'.$this->realm.'"';
                $app->halt(401,'Please authenticate');
            } else {
                $app->getLog()->debug('login: redirecting to login');
                $app->redirect($request->getRootUri().'/login/');
            }
        }
    }

    /**
     * Static resources must not be protected. Return true id the requested resource 
     * belongs to a static resource path, else false.
     */
    protected function is_static_resource($resource) {
        $path_parts = split("/", $resource);
        if (isset($path_parts)) {
            foreach ($this->static_resource_paths as $static_resource_path) {
                if (strcasecmp($static_resource_path, $path_parts[1]) == 0)
                    return true;
            }    
        }
        return false;
    }

    /**
     * Check if the access request is authorized by a user. A request must either contain session data from 
     * a previous login or contain a HTTP Basic <em>Authorization</em> header, which is then used to
     * perform a login against the users table in the database. 
     */
    protected function is_authorized() {
        $app = $this->app;
        $req = $app->request;
        //$bbs = new BicBucStriim();
        //$strong = new Strong(array('provider' => 'PDO', 'pdo' => $bbs->mydb));
        if ($app->strong->loggedIn()) {
            return true;
        } else {
            $b64auth = $req->headers('Authorization');
            if (isset($b64auth)) {
                $auth_array1 = split(" ", $b64auth);
                if (!isset($auth_array1) || strcasecmp('Basic', $auth_array1[0]) != 0)
                    return false;
                if (sizeof($auth_array1) != 2 || !isset($auth_array1[1]))
                    return false;
                $auth = base64_decode($auth_array1[1]);
                $auth_array2 = split(":", $auth);
                return $app->strong->login($auth_array2[0], $auth_array2[1]);
            } else {
                return false;
            }
        }
    }
}
?>
