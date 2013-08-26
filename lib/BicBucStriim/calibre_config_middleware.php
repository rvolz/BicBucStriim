<?php

require 'vendor/autoload.php';
require_once 'lib/BicBucStriim/calibre.php';

class CalibreConfigMiddleware extends \Slim\Middleware {

	protected $calibreDir;

	/**
     * Initialize the configuration
     *
     * @param array $config
     */
    public function __construct($calibreDir) {
        $this->calibreDir = $calibreDir;
    }

	/**
	 * Check if the Calibre configuration is valid: 
	 * - If Calibre dir is undefined -> goto admin page
	 * - If Calibre cannot be opened -> goto admin page
	 */
	public function call() {
		global $globalSettings;
		$app = $this->app;
		$request = $app->request;
		
		if ($request->getResourceUri() != '/login/') {
			# 'After installation' scenario: here is a config DB but no valid connection to Calibre
			if (empty($globalSettings[$this->calibreDir])) {
				$app->getLog()->warn('check_config: Calibre library path not configured.');					
				if ($app->request->getResourceUri() != '/admin/configuration/') {
					// app->redirect not useable in middleware
					$app->response->status(302);
					$app->response->header('Location', $app->request->getRootUri().'/admin/configuration/');
				} else {
					$this->next->call();		
				}
			} else {
				# Setup the connection to the Calibre metadata db
				$clp = $globalSettings[$this->calibreDir].'/metadata.db';
				$app->calibre = new Calibre($clp);
				if (!$app->calibre->libraryOk()) {
					$app->getLog()->error('check_config: Exception while opening metadata db '.$clp.'. Showing admin page.');	
					// app->redirect not useable in middleware
					$app->response->status(302);
					$app->response->header('Location', $app->request->getRootUri().'/admin/configuration/');
				} else {
					$this->next->call();		
				}
			}				
		}  else {
			$this->next->call();
		}
	}
}
?>
