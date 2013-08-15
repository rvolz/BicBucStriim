<?php

require 'vendor/autoload.php';
require_once 'lib/BicBucStriim/bicbucstriim.php';
require_once 'lib/BicBucStriim/bbs_pdo.php';
use Strong\Strong;

class OwnConfigMiddleware extends \Slim\Middleware {

	protected $knownConfigs;

	/**
     * Initialize the configuration
     *
     * @param array $config
     */
    public function __construct($knownConfigs) {
        $this->knownConfigs = $knownConfigs;
    }

	public function call() {
		global $globalSettings;
		$app = $this->app;
		if (!$this->check_config_db()) {
			// TODO severe error message + redirect to installcheck.php
			$app->halt(500, 'No or bad configuration database. Please use < href="'.
				$app->request->getRootUri().
				'/installcheck.php">installcheck.php</a> to check for errors.');
		} else {
			$this->next->call();
		}

	}

	protected function check_config_db() {
		global $globalSettings, $we_have_config;
		$we_have_config = false;
		$app = $this->app;
		if ($app->bbs->dbOk()) {
			$we_have_config = true;
			$css = $app->bbs->configs();
			foreach ($css as $config) {
				if (in_array($config->name, $this->knownConfigs)) 
					$globalSettings[$config->name] = $config->val;
				else 
					$app->getLog()->warn(join('',
						array('Unknown configuration, name: ', $config->name,', value: ',$config->val)));	
			}
			if (!isset($app->strong)) 
				$app->strong = $this->getAuthProvider($app->bbs->mydb);
			$app->getLog()->debug("config loaded");
		} else {
			$app->getLog()->info("no config db found - creating a new one with default values");
			$app->bbs->createDataDb();
			$app->bbs = new BicBucStriim();
			$cnfs = array();
			foreach($this->knownConfigs as $name) {
				$cnf = new Config();
				$cnf->name = $name;
				$cnf->val = $globalSettings[$name];
				array_push($cnfs, $cnf);
			}
			$app->bbs->saveConfigs($cnfs);
			if (!isset($app->strong)) 
				$app->strong = $this->getAuthProvider($app->bbs->mydb);
			$we_have_config = true;
		}
		return $we_have_config;
	}

	protected function getAuthProvider($db) {
		$provider = new BBSPDO(array('pdo' => $db));
		return new Strong(array('provider' => $provider, 'pdo' => $db));;
	}
}
?>
