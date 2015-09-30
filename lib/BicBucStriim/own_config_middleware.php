<?php

require 'vendor/autoload.php';
require_once 'lib/BicBucStriim/bicbucstriim.php';

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
		$config_status = $this->check_config_db();
		if ($config_status == 0) {
			$app->halt(500, 'No or bad configuration database. Please use <a href="'.
				$app->request->getRootUri().
				'/installcheck.php">installcheck.php</a> to check for errors.');
		} elseif ($config_status == 2) {
			// TODO Redirect to an update script in the future
			$app->halt(500, 'Old configuration database detected. Please refer to the <a href="http://projekte.textmulch.de/bicbucstriim/#upgrading">upgrade documentation</a> for more information.');
		} else {
			$this->next->call();
		}

	}

	protected function check_config_db() {
		global $globalSettings, $we_have_config;
		$we_have_config = 0;
		$app = $this->app;
		if ($app->bbs->dbOk()) {
			$we_have_config = 1;
			$css = $app->bbs->configs();
			foreach ($css as $config) {
				if (in_array($config->name, $this->knownConfigs)) 
					$globalSettings[$config->name] = $config->val;
				else 
					$app->getLog()->warn(join('own_config_middleware: ',
						array('Unknown configuration, name: ', $config->name,', value: ',$config->val)));	
			}

			if ($globalSettings[DB_VERSION] != DB_SCHEMA_VERSION) {
				$app->getLog()->warn('own_config_middleware: old db schema detected. please run update');							
				return 2;
			}

			if ($globalSettings[LOGIN_REQUIRED] == 1) {
				$app->must_login = true;
				$app->getLog()->info('multi user mode: login required');	
			} else {
				$app->must_login = false;
				$app->getLog()->debug('easy mode: login not required');	
			}
			$app->getLog()->debug("own_config_middleware: config loaded");
		} else {
			$app->getLog()->info("own_config_middleware: no config db found - creating a new one with default values");
			$app->bbs->createDataDb();
			$app->bbs = new BicBucStriim('data/data.db', 'data');
			$cnfs = array();
			foreach($this->knownConfigs as $name) {
				$cnf = R::dispense('config');
				$cnf->name = $name;
				$cnf->val = $globalSettings[$name];
				array_push($cnfs, $cnf);
			}
			$app->bbs->saveConfigs($cnfs);
			$we_have_config = 1;
		}
		return $we_have_config;
	}

}
?>
