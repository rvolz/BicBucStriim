<?php
/**
 * BicBucStriim
 *
 * Copyright 2012-2013 Rainer Volz
 * Licensed under MIT License, see LICENSE
 * 
 */ 
require 'vendor/autoload.php';
require_once 'lib/BicBucStriim/langs.php';
require_once 'lib/BicBucStriim/l10n.php';
require_once 'lib/BicBucStriim/bicbucstriim.php';
require_once 'lib/BicBucStriim/opds_generator.php';
require_once 'lib/BicBucStriim/own_config_middleware.php';
require_once 'lib/BicBucStriim/calibre_config_middleware.php';
require_once 'vendor/email.php';

# Allowed languages, i.e. languages with translations
$allowedLangs = array('de','en','fr','nl');
# Fallback language if the browser prefers other than the allowed languages
$fallbackLang = 'en';
# Application Name
$appname = 'BicBucStriim';
# App version
$appversion = '1.2.0-alpha';
# Current DB schema version
define('DB_SCHEMA_VERSION', '3');

# URL for version information
define('VERSION_URL', 'http://projekte.textmulch.de/bicbucstriim/version.json');
# Cookie name for global download protection
define('GLOBAL_DL_COOKIE', 'glob_dl_access');
# Cookie name for admin access
define('ADMIN_COOKIE', 'admin_access');
# Cookie name to store Kindle email address
define('KINDLE_COOKIE', 'kindle_email');
# Admin password
define('ADMIN_PW', 'admin_pw');
# Calibre library path
define('CALIBRE_DIR', 'calibre_dir');
# Global download choice
define('GLOB_DL_CHOICE', 'glob_dl_choice');
# Global download password
define('GLOB_DL_PASSWORD', 'glob_dl_password');
# Global tags choice
define('TAG_PROTECT_CHOICE', 'tag_protect_choice');
# Global tag name protect
define('TAG_PROTECT_FIELD', 'tag_protect_field');
# BicBucStriim DB version
define('DB_VERSION', 'db_version');
# Thumbnail generation method
define('THUMB_GEN_CLIPPED', 'thumb_gen_clipped');
# Send-To-Kindle enabled/disabled
define('KINDLE', 'kindle');
# Send-To-Kindle from-address
define('KINDLE_FROM_EMAIL', 'kindle_from_email');
# Page size for list views, no. of elemens
define('PAGE_SIZE', 'page_size');
# Displayed app name for page title
define('DISPLAY_APP_NAME', 'display_app_name');

# Init app and routes
$app = new \Slim\Slim(array(
	'view' => new \Slim\Views\Twig(),
	#'mode' => 'production',
	'mode' => 'development',
));

$app->configureMode('production','confprod');
$app->configureMode('development','confdev');
$app->configureMode('debug','confdebug');

/**
 * Configure app for production
 */
function confprod() {
	global $app, $appname, $appversion;
	$app->config(array(
		'debug' => false,
		'cookies.lifetime' => '1 day',
		'cookies.secret_key' => 'b4924c3579e2850a6fad8597da7ad24bf43ab78e',

	));
	$app->getLog()->setEnabled(true);
	$app->getLog()->setLevel(\Slim\Log::WARN);
	$app->getLog()->info($appname.' '.$appversion.': Running in production mode.');
}

/**
 * Configure app for development
 */
function confdev() {
	global $app, $appname, $appversion;
	$app->config(array(
		'debug' => true,
		'cookies.lifetime' => '5 minutes',
		'cookies.secret_key' => 'b4924c3579e2850a6fad8597da7ad24bf43ab78e',

	));
	$app->get('/dev/reset', 'devReset');
	$app->getLog()->setEnabled(true);
	$app->getLog()->setLevel(\Slim\Log::DEBUG);
	$app->getLog()->info($appname.' '.$appversion.': Running in development mode.');
}

/**
 * Configure app for debug mode: production + log everything to file
 */
function confdebug() {
	global $app, $appname, $appversion;
	$app->config(array(
		'debug' => true,
		'cookies.lifetime' => '1 day',
		'cookies.secret_key' => 'b4924c3579e2850a6fad8597da7ad24bf43ab78e',
	));
	$app->getLog()->setEnabled(true);
	$app->getLog()->setLevel(\Slim\Log::DEBUG);
	$app->getLog()->setWriter(new \Slim\Extras\Log\DateTimeFileWriter(array('path' => './data', 'name_format' => 'Y-m-d\.\l\o\g')));
	$app->getLog()->info($appname.' '.$appversion.': Running in debug mode.');
}

# Init app globals
$globalSettings = array();
$globalSettings['appname'] = $appname;
$globalSettings['version'] = $appversion;
$globalSettings['sep'] = ' :: ';
# Find the user language, either one of the allowed languages or
# English as a fallback.
$globalSettings['lang'] = getUserLang($allowedLangs, $fallbackLang);
$globalSettings['l10n'] = new L10n($globalSettings['lang']);
$globalSettings['langa'] = $globalSettings['l10n']->langa;
$globalSettings['langb'] = $globalSettings['l10n']->langb;
# Init admin settings with std values, for upgrades or db errors
$globalSettings[ADMIN_PW] = '';
$globalSettings[CALIBRE_DIR] = '';
$globalSettings[DB_VERSION] = DB_SCHEMA_VERSION;
$globalSettings[GLOB_DL_PASSWORD] = '7094e7dc2feb759758884333c2f4a6bdc9a16bb2';
$globalSettings[GLOB_DL_CHOICE] = 0;
$globalSettings[TAG_PROTECT_FIELD] = 'protected';
$globalSettings[TAG_PROTECT_CHOICE] = 0;
$globalSettings[KINDLE] = 0;
$globalSettings[KINDLE_FROM_EMAIL] = '';
$globalSettings[THUMB_GEN_CLIPPED] = 1;
$globalSettings[PAGE_SIZE] = 30;
$globalSettings[DISPLAY_APP_NAME] = $appname;

$knownConfigs = array(ADMIN_PW, CALIBRE_DIR, DB_VERSION, GLOB_DL_PASSWORD, GLOB_DL_CHOICE, 
	TAG_PROTECT_CHOICE, TAG_PROTECT_FIELD, KINDLE, KINDLE_FROM_EMAIL, THUMB_GEN_CLIPPED, PAGE_SIZE, 
	DISPLAY_APP_NAME);

# Check if libmcrypt is available
$globalSettings['crypt'] = function_exists('mcrypt_encrypt');
$app->getLog()->info('Encryption '.($globalSettings['crypt']==true ? '' : 'not ').'available');

$bbs = new BicBucStriim();
$app->bbs = $bbs;
$app->add(new \CalibreConfigMiddleware(CALIBRE_DIR));
$app->add(new \OwnConfigMiddleware($knownConfigs));

###### Init routes for production
$app->notFound('myNotFound');
$app->get('/', 'main');
$app->get('/admin/', 'admin');
$app->post('/admin/', 'admin_change_json');
$app->post('/admin/access/check/', 'admin_checkaccess');
$app->get('/admin/version/', 'admin_check_version');
$app->get('/authors/:id/:page/', 'authorDetailsSlice');
$app->get('/authorslist/:id/', 'authorsSlice');
$app->get('/search/', 'globalSearch');
$app->get('/series/:id/:page/', 'seriesDetailsSlice');
$app->get('/serieslist/:id/', 'seriesSlice');
$app->get('/tags/:id/:page/', 'tagDetailsSlice');
$app->get('/tagslist/:id/', 'tagsSlice');
$app->get('/titles/:id/', 'title');
$app->get('/titles/:id/showaccess/',  'showaccess');
$app->post('/titles/:id/checkaccess/',  'checkaccess');
$app->get('/titles/:id/cover/', 'cover');
$app->get('/titles/:id/file/:file', 'book');
$app->post('/titles/:id/kindle/:file', 'kindle');
$app->get('/titles/:id/thumbnail/', 'thumbnail');
$app->get('/titleslist/:id/', 'titlesSlice');
$app->get('/opds/', 'opdsCheckConfig', 'opdsRoot');
$app->get('/opds/newest/', 'opdsCheckConfig', 'opdsNewest');
$app->get('/opds/titleslist/:id/', 'opdsCheckConfig', 'opdsByTitle');
$app->get('/opds/authorslist/', 'opdsCheckConfig', 'opdsByAuthorInitial');
$app->get('/opds/authorslist/:initial/', 'opdsCheckConfig', 'opdsByAuthorNamesForInitial');
$app->get('/opds/authorslist/:initial/:id/', 'opdsCheckConfig', 'opdsByAuthor');
$app->get('/opds/tagslist/', 'opdsCheckConfig', 'opdsByTagInitial');
$app->get('/opds/tagslist/:initial/', 'opdsCheckConfig', 'opdsByTagNamesForInitial');
$app->get('/opds/tagslist/:initial/:id/', 'opdsCheckConfig', 'opdsByTag');
$app->get('/opds/serieslist/', 'opdsCheckConfig', 'opdsBySeriesInitial');
$app->get('/opds/serieslist/:initial/', 'opdsCheckConfig', 'opdsBySeriesNamesForInitial');
$app->get('/opds/serieslist/:initial/:id/', 'opdsCheckConfig', 'opdsBySeries');
$app->get('/opds/opensearch.xml', 'opdsCheckConfig', 'opdsSearchDescriptor');
$app->get('/opds/searchlist/:id/', 'opdsCheckConfig', 'opdsBySearch');
$app->run();

/*********************************************************************
 * Development only functions
 ********************************************************************/

/**
 * Reset the database and delete all dynamic data (thumbnails etc.)
 * for testing.
 */
function devReset() {
	system("cp data/data.backup data/data.db");
	system("rm data/thm*.png");
}

/*********************************************************************
 * Production functions
 ********************************************************************/

/**
 * Check the configuration DB and open it
 * @return int 0 = ok
 *             1 = no config db
 *             2 = no calibre library path defined (after installation scenario)
 *             3 = error while opening the calibre db 
 */
function check_config() {
	global $we_have_config, $bbs, $app, $globalSettings;

	$app->getLog()->debug('check_config started');
	# No config --> error
	if (!$we_have_config) {
		$app->getLog()->error('check_config: No configuration found');
		return(1);
	}

	# 'After installation' scenario: here is a config DB but no valid connection to Calibre
	if ($we_have_config && $globalSettings[CALIBRE_DIR] === '') {
		if ($app->request()->isPost() && $app->request()->getResourceUri() === '/admin/') {
			# let go through
		} else {
			$app->getLog()->warn('check_config: Calibre library path not configured, showing admin page.');	
			return(2);
		}
	}

	# Setup the connection to the Calibre metadata db
	$clp = $globalSettings[CALIBRE_DIR].'/metadata.db';
	$bbs->openCalibreDB($clp);
	if (!$bbs->libraryOk()) {
		$app->getLog()->error('check_config: Exception while opening metadata db '.$clp.'. Showing admin page.');	
		return(3);
	} 	
	$app->getLog()->debug('check_config ended');
	return(0);
}

# Check if the configuration is valid:
# - If there is no bbs db --> show error

/**
 * Check if the configuration is valid: 
 * - If there is no bbs db --> show error
 * - If Calibre dir is undefined -> goto admin page
 * - If Calibre dir is undefined -> goto admin page
 */
function htmlCheckConfig() {
	global $app, $globalSettings;

	$result = check_config();

	# No config --> error
	if ($result === 1) {
		$app->render('error.html', array(
			'page' => mkPage(getMessageString('error')), 
			'title' => getMessageString('error'), 
			'error' => getMessageString('no_config')));
		return;
	} elseif ($result === 2) {
		# After installation, no calibre dir defined, goto admin page
		$app->redirect($app->request()->getRootUri().'/admin');
		return;
	} elseif ($result === 3) {
		# Calibre dir wrong? Goto admin page
		$app->redirect($app->request()->getRootUri().'/admin');
		return;
	} 
}

/**
 * 404 page for invalid URLs
 */
function myNotFound() {
	global $app, $globalSettings;
	$app->render('error.html', array(
		'page' => mkPage(getMessageString('not_found1')), 
		'title' => getMessageString('not_found1'), 
		'error' => getMessageString('not_found2')));
}

function show_login() {
	global $app, $globalSettings;
	$app->render('login.html', array(
		'page' => mkPage(getMessageString('login')))); 
}

function perform_login() {
	global $app, $globalSettings, $bbs;
	$login_data = $app->request()->post();
	$app->getLog()->debug('login: '.var_export($login_data,true));	
	if (isset($login_data['username']) && isset($login_data['password'])) {
		$uname = $login_data['username'];
		$upw = $login_data['password'];
		if (empty($uname) || empty($upw)) {
			$app->render('login.html', array(
				'page' => mkPage(getMessageString('login')))); 			
		} else {
			$app->redirect($app->getRootUri());
		}
	} else {
		$app->render('login.html', array(
			'page' => mkPage(getMessageString('login')))); 			
	}
}


/**
 * Generate the main page with the 30 mos recent titles
 */
function main() {
	global $app, $globalSettings, $bbs;

	$ts1 = time();
	$books = $bbs->last30Books($globalSettings[PAGE_SIZE]);
	$ts2 = time();
	$formats = array();
	foreach ($books as $book) {
		$book->formats = $bbs->titleGetFormats($book->id);
	}
	$ts3 = time();

	$app->render('index_last30.html',array(
		'page' => mkPage(getMessageString('dl30'),1), 
		'books' => $books));	
	$app->getLog()->debug(sprintf("main: time getting books %u, time getting formats %u", $ts2-$ts1, $ts3-$ts2));	
}

/**
 * Generate the admin page -> /admin/
 */
function admin() {
	global $app, $globalSettings, $bbs;

	$app->render('admin.html',array(
		'page' => mkPage(getMessageString('admin')),
		'isadmin' => is_admin()));
}

/**
 * Is the key in globalSettings?
 * @param  string  	$key 	key for config value
 * @return boolean      	true = key available
 */
function has_global_setting($key) {
	return (isset($globalSettings[$key]) && !empty($globalSettings[$key]));
}

/**
 * Is there a valid - existing - Calibre directory?
 * @return boolean 	true if available
 */
function has_valid_calibre_dir() {
	return (has_global_setting(CALIBRE_DIR) && 
		BicBucStriim::checkForCalibre($globalSettings[CALIBRE_DIR]));
}

/**
 * Check for admin permissions. If no admin password is defined 
 * everyone has admin permissions.
 */
function is_admin() {
	$apw = getAdminPassword();
	if (is_null($apw))
		return true;
	else {
		$admin_cookie = getOurCookie(ADMIN_COOKIE);
		if (!is_null($admin_cookie) && $admin_cookie === $apw)
			return true;
		else
			return false;
	}	
}

/**
 * Processes changes in the admin page -> POST /admin/
 */
function admin_change_json() {
	global $app, $globalSettings, $bbs;
	$app->getLog()->debug('admin_change: started');	
	# Check access permission
	if (!is_admin()) {
		$app->getLog()->warn('admin_change: no admin permission');	
		$app->render('admin.html',array(
			'page' => mkPage(getMessageString('admin')),
			'messages' => array(getMessageString('invalid_password')),
			'isadmin' => false));
		return;
	}
	$nconfigs = array();
	$req_configs = $app->request()->post();
	$errors = array();
	$messages = array();
	$app->getLog()->debug('admin_change: '.var_export($req_configs,true));	

	## For 1.0: run a silent db update
	# TODO post 1.0: replace with an updater 
	if ($globalSettings[DB_VERSION] =! DB_SCHEMA_VERSION) {
		$app->getLog()->warn('admin_change: old db schema detected. running update');							
		$bbs->updateDbSchema1to2();		
	}

	## Check for consistency - calibre directory
	# Calibre dir is still empty and no change in sight --> error
	if (!has_valid_calibre_dir() && empty($req_configs[CALIBRE_DIR]))
		array_push($errors, 1);
	# Calibre dir changed, check it for existence
	elseif (array_key_exists(CALIBRE_DIR, $req_configs)) {		
		$req_calibre_dir = $req_configs[CALIBRE_DIR];
		if ($req_calibre_dir != $globalSettings[CALIBRE_DIR]) {
			if (!BicBucStriim::checkForCalibre($req_calibre_dir))
				array_push($errors, 1);
		}
	} 
	## More consistency checks - download protection
	# Switch off DL protection, if there is a problem with the configuration
	if ($req_configs[GLOB_DL_CHOICE] != "0") {
		if($req_configs[GLOB_DL_CHOICE] == "1" && empty($req_configs[ADMIN_PW])) {
			array_push($errors, 3);
		} elseif ($req_configs[GLOB_DL_CHOICE] == "2" && empty($req_configs[GLOB_DL_PASSWORD])) {
			array_push($errors, 2);
		}
	}			
	## More consistency checks - kindle feature
	# Switch off Kindle feature, if no valid email address supplied 
	if ($req_configs[KINDLE] == "1") {
		if(empty($req_configs[KINDLE_FROM_EMAIL])) {
			array_push($errors, 5);
		} elseif (!isEMailValid($req_configs[KINDLE_FROM_EMAIL])) {
			array_push($errors, 5);
		}
	}			

	## Check for a change in the thumbnail generation method
	if ($req_configs[THUMB_GEN_CLIPPED] != $globalSettings[THUMB_GEN_CLIPPED]) {
		$app->getLog()->info('admin_change: Thumbnail generation method changed. Exisiting Thumbnails will be deleted.');		
		# Delete old thumbnails if necessary
		if($bbs->clearThumbnails())
			$app->getLog()->info('admin_change: Deleted exisiting thumbnails.');
		else {
			$app->getLog()->info('admin_change: Deletion of exisiting thumbnails failed.');
		}
	}

	## Check for a change in page size, min 1, max 100
	if ($req_configs[PAGE_SIZE] != $globalSettings[PAGE_SIZE]) {
		if ($req_configs[PAGE_SIZE] < 1 || $req_configs[PAGE_SIZE] > 100) {
			$app->getLog()->warn('admin_change: Invalid page size requested: '.$req_configs[PAGE_SIZE]);
			array_push($errors, 4);
		}
	}
	
	# Don't save just return the error status
	if (count($errors) > 0) {
		$app->getLog()->error('admin_change: ended with error '.var_export($errors, true));	
		$app->render('admin.html',array(
		'page' => mkPage(getMessageString('admin')), 
		'isadmin' => true,
		'errors' => $errors));	
	} else {
		## Apply changes 
		foreach ($req_configs as $key => $value) {
			if (!isset($globalSettings[$key]) || $value != $globalSettings[$key]) {
				$c1 = new Config();
				$c1->name = $key;
				$c1->val = $value;
				array_push($nconfigs,$c1);
				$globalSettings[$key] = $value;
				$app->getLog()->debug('admin_change: '.$key.' changed: '.$value);	
			}
		}
		# Save changes
		if (count($nconfigs) > 0) {
			$bbs->saveConfigs($nconfigs);
			$app->getLog()->debug('admin_change: changes saved');					
		}
		$app->getLog()->debug('admin_change: ended');	
		$app->render('admin.html',array(
			'page' => mkPage(getMessageString('admin')), 
			'messages' => array(getMessageString('changes_saved')),
			'isadmin' => true,
			));	
	}
}

/**
 * Checks access to the admin page -> /admin/access/check
 */
function admin_checkaccess() {
	global $app, $globalSettings;

	$app->deleteCookie(ADMIN_COOKIE);
	$password = $app->request()->post('admin_pwin');
	$app->getLog()->debug('admin_checkaccess input: '.$password);

	$apw = getAdminPassword();
	if ($password == $apw) {
		$app->getLog()->debug('admin_checkaccess succeded');
		setOurCookie(ADMIN_COOKIE,$password);
		$app->redirect($app->request()->getRootUri().'/admin/');
	} else {		
		$app->getLog()->debug('admin_checkaccess failed');
		#$app->response()->status(401);
		$app->render('admin.html',array(
			'page' => mkPage(getMessageString('admin')),
			'messages' => array(getMessageString('invalid_password')),
			'isadmin' => false));
	}
}


function admin_check_version() {
	global $app, $globalSettings;	
	$app->getLog()->debug("admin version-check started");
	$versionAnswer = array();
	$contents = file_get_contents(VERSION_URL);	
	if ($contents == false) {
		$versionAnswer['newVersion'] = 'false';
		$msg1 = sprintf(getMessageString('admin_new_version_error'),$globalSettings['version']);
		$versionAnswer['msg'] = '<p class="success">'.$msg1.'</p>'; 
	} else {
		$versionInfo = json_decode($contents);	
		
		if ($versionInfo->{'version'} > $globalSettings['version']) {
			$versionAnswer['newVersion'] = 'true';
			$msg1 = sprintf(getMessageString('admin_new_version'),$versionInfo->{'version'},$globalSettings['version']);
			$msg2 = sprintf("<a href=\"%s\">%s</a>",$versionInfo->{'url'},$versionInfo->{'url'});
			$msg3 = sprintf(getMessageString('admin_check_url'),$msg2);
			$versionAnswer['msg'] = '<p class="success">'.$msg1.'. '.$msg3.'</p>'; 
			$app->getLog()->debug("admin version-check new version ".$versionInfo->{'version'});
		} else {
			$versionAnswer['newVersion'] = 'false';
			$msg1 = sprintf(getMessageString('admin_no_new_version'),$globalSettings['version']);
			$versionAnswer['msg'] = '<p class="success">'.$msg1.'</p>'; 
		}		
	}
	$answer = json_encode($versionAnswer);
	$app->getLog()->debug("admin version-check ended");
	$app->response()->status(200);
	$app->response()->header('Content-type','application/json');
	$app->response()->header('Content-Length',strlen($answer));
	$app->response()->body($answer);
}

# Check if the admin page is protected by a password
# -> /admin/access/
function admin_is_protected() {
	global $app, $globalSettings;

	$apw = getAdminPassword();
	if (!is_null($apw)) {
		$app->getLog()->debug('admin_is_protected: yes');
		$app->response()->status(200);
		$app->response()->body('1');
	} else {		
		$app->getLog()->debug('admin_is_protected: no');
		$app->response()->status(200);
		$app->response()->body('0');
	}	
}

# Make a search over all categories. Returns only the first PAGES_SIZE items per category.
# If there are more entries per category, there will be a link to the full results.
function globalSearch() {
	global $app, $globalSettings, $bbs;

	$search = $app->request()->get('search');
	$app->getLog()->debug("globalSearch started for search ".$search);
	$tlb = $bbs->titlesSlice(0,$globalSettings[PAGE_SIZE],trim($search));
	$tla = $bbs->authorsSlice(0,$globalSettings[PAGE_SIZE],trim($search));
	$tlt = $bbs->tagsSlice(0,$globalSettings[PAGE_SIZE],trim($search));
	$tls = $bbs->seriesSlice(0,$globalSettings[PAGE_SIZE],trim($search));
	foreach ($tlb['entries'] as $book)
		$book->formats = $bbs->titleGetFormats($book->id);
	$app->render('global_search.html',array(
		'page' => mkPage(getMessageString('pagination_search'),0), 
		'books' => $tlb['entries'],
		'books_total' => $tlb['total'] == -1 ? 0 : $tlb['total'],
		'more_books' => ($tlb['total'] > $globalSettings[PAGE_SIZE]),
		'authors' => $tla['entries'],
		'authors_total' => $tla['total'] == -1 ? 0 : $tla['total'],
		'more_authors' => ($tla['total'] > $globalSettings[PAGE_SIZE]),
		'tags' => $tlt['entries'],
		'tags_total' => $tlt['total'] == -1 ? 0 : $tlt['total'],
		'more_tags' => ($tlt['total'] > $globalSettings[PAGE_SIZE]),
		'series' => $tls['entries'],
		'series_total' => $tls['total'] == -1 ? 0 : $tls['total'],
		'more_series' => ($tls['total'] > $globalSettings[PAGE_SIZE]),
		'search' => $search));
}

# A list of titles at $index -> /titlesList/:index
function titlesSlice($index=0) {
	global $app, $globalSettings, $bbs;

	$app->getLog()->debug("titlesSlice started for index ".$index);
	$search = $app->request()->get('search');
	if (isset($search)) {
		$app->getLog()->debug("search ".$search);
		$tl = $bbs->titlesSlice($index,$globalSettings[PAGE_SIZE],trim($search));
	} else
		$tl = $bbs->titlesSlice($index,$globalSettings[PAGE_SIZE]);
	foreach ($tl['entries'] as $book)
		$book->formats = $bbs->titleGetFormats($book->id);
	$app->render('titles.html',array(
		'page' => mkPage(getMessageString('titles'),2), 
		'url' => 'titleslist',
		'books' => $tl['entries'],
		'curpage' => $tl['page'],
		'pages' => $tl['pages'],
		'search' => $search));
}

# Show a single title > /titles/:id. The ID ist the Calibre ID
function title($id) {
	global $app, $calibre_dir, $globalSettings, $bbs;
	
	$details = $bbs->titleDetails($id);	
	if (is_null($details)) {
		$app->getLog()->debug("title: book not found: ".$id);
		$app->notFound();
		return;
	}	
	$ccs = $bbs->customColumns($id);
	sort($ccs);
	$kindle_format = ($globalSettings[KINDLE] == 1) ? $bbs->titleGetKindleFormat($id): NULL;
	$app->render('title_detail.html',
		array('page' => mkPage(getMessageString('book_details')), 
			'calibre_dir' => $calibre_dir,
			'book' => $details['book'], 
			'authors' => $details['authors'],
			'series' => $details['series'],
			'tags' => $details['tags'], 
			'formats'=>$details['formats'], 
			'comment' => $details['comment'],
			'language' => $details['language'],
			'ccs' => $ccs,
			'kindle_format' => $kindle_format,
			'kindle_from_email' => $globalSettings[KINDLE_FROM_EMAIL],
			'protect_dl' => is_protected($id))
	);
}

# Show the password dialog
# Route: /titles/:id/showaccess/
function showaccess($id) {
	global $app, $globalSettings;

	$app->getLog()->debug('showaccess called for '.$id);			
	$app->render('password_dialog.html',
		array('page' => mkPage(getMessageString('check_access'),0,true), 
					'bookid' => $id));
}

/**
 * Check the access rights for a book and set a cookie if successful.
 * Sends 404 if unsuccessful.
 * Route: /titles/:id/checkaccess/
 *
 * If libmcrypt is available, encrypted cookies are used.
 * 
 * @param  int 		$id book id
 */
function checkaccess($id) {
	global $app, $calibre_dir, $globalSettings, $bbs;

	$rot = $app->request()->getRootUri();
	$book = $bbs->title($id);
	if (is_null($book)) {
		$app->getLog()->debug("checkaccess: book not found: ".$id);
		$app->response()->status(404);
		return;
	}

	$app->deleteCookie(GLOBAL_DL_COOKIE);
	$password = $app->request()->post('password');
	$app->getLog()->debug('checkaccess input: '.$password);

	$cpw = getDownloadPassword();

	if ($password == $cpw) {
		$app->getLog()->debug('checkaccess succeded');

		setOurCookie(GLOBAL_DL_COOKIE, $cpw);
		$app->response()->status(200);
	} else {		
		$app->getLog()->debug('checkaccess failed');
		$app->flash('error', $globalSettings['langa']['invalid_password']);
		$app->response()->status(404);
	}
}

# Return the cover for the book with ID. Calibre generates only JPEGs, so we always return a JPEG.
# If there is no cover, return 404.
# Route: /titles/:id/cover
function cover($id) {
	global $app, $calibre_dir, $bbs;

	$has_cover = false;
	$rot = $app->request()->getRootUri();
	$book = $bbs->title($id);
	if (is_null($book)) {
		$app->getLog()->debug("cover: book not found: "+$id);
		$app->response()->status(404);
		return;
	}
	
	if ($book->has_cover) {		
		$cover = $bbs->titleCover($id);
		$has_cover = true;
	}
	if ($has_cover) {
		$app->response()->status(200);
		$app->response()->header('Content-type','image/jpeg;base64');
		$app->response()->header('Content-Length',filesize($cover));
		readfile($cover);		
	} else {
		$app->response()->status(404);
	}
}

# Return the cover for the book with ID. Calibre generates only JPEGs, so we always return a JPEG.
# If there is no cover, return 404.
# Route: /titles/:id/thumbnail
function thumbnail($id) {
	global $app, $calibre_dir, $bbs, $globalSettings;

	$has_cover = false;
	$rot = $app->request()->getRootUri();
	$book = $bbs->title($id);
	if (is_null($book)) {
		$app->getLog()->error("thumbnail: book not found: "+$id);
		$app->response()->status(404);
		return;
	}
	
	if ($book->has_cover) {		
		$thumb = $bbs->titleThumbnail($id, $globalSettings[THUMB_GEN_CLIPPED]);
		$has_cover = true;
	}
	if ($has_cover) {
		$app->response()->status(200);
		$app->response()->header('Content-type','image/jpeg;base64');
		$app->response()->header('Content-Length',filesize($thumb));
		readfile($thumb);		
	} else {
		$app->response()->status(404);
	}
}


# Return the selected file for the book with ID. 
# Route: /titles/:id/file/:file
function book($id, $file) {
	global $app, $bbs;

	$book = $bbs->title($id);
	if (is_null($book)) {
		$app->getLog()->debug("no book file");
		$app->notFound();
	}	
	if (is_protected($id)) {
		$app->getLog()->warn("book: attempt to download a protected book, ".$id);		
		$app->response()->status(401);
	} else {
		$app->getLog()->debug("book: file ".$file);
		$bookpath = $bbs->titleFile($id, $file);
		$app->getLog()->debug("book: path ".$bookpath);

		/** readfile has problems with large files (e.g. PDF) caused by php memory limit
		 * to avoid this the function readfile_chunked() is used. app->response() is not
		 * working with this solution.
		**/
		//TODO: Use new streaming functions in SLIM 1.7.0 when released
		header("Content-length: ".filesize($bookpath));
		header("Content-type: ".Utilities::titleMimeType($bookpath));
		readfile_chunked($bookpath);
	}
}


# Send the selected file to a Kindle e-mail address
# Route: /titles/:id/kindle/:file
function kindle($id, $file) {
	global $app, $bbs, $globalSettings;
	$book = $bbs->title($id);
	if (is_null($book)) {
		$app->getLog()->debug("kindle: book not found: ".$id);
		$app->response()->status(404);
		return;
	}	
	if (is_protected($id)) {
		$app->getLog()->warn("kindle: attempt to send a protected book, ".$id);		
		$app->response()->status(401);
		return;
	}
	# Validate request e-mail format
	$to_email = $app->request()->post('email');
	if (!isEMailValid($to_email)) {
		$app->getLog()->debug("kindle: invalid email, ".$to_email);	
		$app->response()->status(400);
		return;
	} else {
		$app->deleteCookie(KINDLE_COOKIE);
		$bookpath = $bbs->titleFile($id, $file);
		$app->getLog()->debug("kindle: requested file ".$bookpath);
		$subject = $globalSettings[DISPLAY_APP_NAME];
		# try to send with email.class
		try {
			$email = new Email($bookpath, $subject, $to_email, $globalSettings[KINDLE_FROM_EMAIL]);
		# if there was an exception, log it and return gracefully
		} catch(Exception $e) {
			$app->getLog()->error('kindle: Email exception '.$e->getMessage());
			$app->response()->status(503);
			return;
		}
		$app->getLog()->debug('kindle: book delivered to '.$to_email);
		# Store e-mail address in cookie so user needs to enter it only once
		$app->setCookie(KINDLE_COOKIE, $to_email);
		echo getMessageString('send_success');
	}
}

# A list of authors at $index -> /authorslist/:index
function authorsSlice($index=0) {
	global $app, $globalSettings, $bbs;

	$search = $app->request()->get('search');
	if (isset($search))
		$tl = $bbs->authorsSlice($index,$globalSettings[PAGE_SIZE],trim($search));	
	else
		$tl = $bbs->authorsSlice($index,$globalSettings[PAGE_SIZE]);
	$app->render('authors.html',array(
		'page' => mkPage(getMessageString('authors'),3), 
		'url' => 'authorslist',
		'authors' => $tl['entries'],
		'curpage' => $tl['page'],
		'pages' => $tl['pages'],
		'search' => $search));
}

/**
 * Details for a single author -> /authors/:id
 * @deprecated since 0.9.3
 */
function author($id) {
	global $app, $globalSettings, $bbs;

	$details = $bbs->authorDetails($id);
	if (is_null($details)) {
		$app->getLog()->debug("no author");
		$app->notFound();		
	}
	$app->render('author_detail.html',array(
		'page' => mkPage(getMessageString('author_details')), 
		'author' => $details['author'], 
		'books' => $details['books']));
}

/**
 * Details for a single author -> /authors/:id/:page/
 * Shows the detail data for the author plus a paginated list of books
 * 
 * @param  integer $id    author id
 * @param  integer $index page index for book list
 * @return HTML page 
 */
function authorDetailsSlice($id, $index=0) {
  global $app, $globalSettings, $bbs;
  
  $app->getLog()->debug('seriesDetailsSlice started with index '.$index);
	$tl = $bbs->authorDetailsSlice($id, $index, $globalSettings[PAGE_SIZE]);
	if (is_null($tl)) {
		$app->getLog()->debug('no author '.$id);
		$app->notFound();
	}
	foreach ($tl['entries'] as $book)
		$book->formats = $bbs->titleGetFormats($book->id);
	$app->render('author_detail.html',array(
		'page' => mkPage(getMessageString('author_details')),
		'url' => 'authors/'.$id,	
		'author' => $tl['author'],
		'books' => $tl['entries'],
		'curpage' => $tl['page'],
		'pages' =>  $tl['pages']));
	}

/**
 * Return a HTML page of series at page $index. 
 * @param  integer $index=0 page index into series list
 */
function seriesSlice($index=0) {
	global $app, $globalSettings, $bbs;

	$app->getLog()->debug('seriesSlice started with index '.$index);			
	$search = $app->request()->get('search');
	if (isset($search)) {
		$app->getLog()->debug('seriesSlice: search '.$search);			
		$tl = $bbs->seriesSlice($index,$globalSettings[PAGE_SIZE],trim($search));	
	} else
		$tl = $bbs->seriesSlice($index,$globalSettings[PAGE_SIZE]);
	$app->render('series.html',array(
		'page' => mkPage(getMessageString('series'),5), 
		'url' => 'serieslist',
		'series' => $tl['entries'],
		'curpage' => $tl['page'],
		'pages' => $tl['pages'],
		'search' => $search));
	$app->getLog()->debug('seriesSlice ended');			
}

/**
 * Return a HTML page with details of series $id, /series/:id
 * @param  int 		$id series id
 * @deprecated since 0.9.3
 */
function series($id) {
	global $app, $globalSettings, $bbs;

	$details = $bbs->seriesDetails($id);
	if (is_null($details)) {
		$app->getLog()->debug('no series '.$id);
		$app->notFound();		
	}
	$app->render('series_detail.html',array(
		'page' => mkPage(getMessageString('series_details')), 
		'series' => $details['series'], 
		'books' => $details['books']));
}

/**
 * Details for a single series -> /series/:id/:page/
 * Shows the detail data for the series plus a paginated list of books
 * 
 * @param  integer $id    series id
 * @param  integer $index page index for books
 * @return HTML page
 */
function seriesDetailsSlice ($id, $index=0) {
  global $app, $globalSettings, $bbs;

	$app->getLog()->debug('seriesDetailsSlice started with index '.$index);
	$tl = $bbs->seriesDetailsSlice($id, $index, $globalSettings[PAGE_SIZE]);
	if (is_null($tl)) {
		$app->getLog()->debug('no series '.$id);
		$app->notFound();		
	}
	foreach ($tl['entries'] as $book)
		$book->formats = $bbs->titleGetFormats($book->id);
	$app->render('series_detail.html',array(
		'page' => mkPage(getMessageString('series_details')),
    'url' => 'series/'.$id, 
		'series' => $tl['series'], 
		'books' => $tl['entries'],
    'curpage' => $tl['page'],
    'pages' => $tl['pages']));   
}


# A list of tags at $index -> /tagslist/:index
function tagsSlice($index=0) {
	global $app, $globalSettings, $bbs;

	$search = $app->request()->get('search');
	if (isset($search))
		$tl = $bbs->tagsSlice($index,$globalSettings[PAGE_SIZE],trim($search));
	else
		$tl = $bbs->tagsSlice($index,$globalSettings[PAGE_SIZE]);
	$app->render('tags.html',array(
		'page' => mkPage(getMessageString('tags'),4), 
		'url' => 'tagslist',
		'tags' => $tl['entries'],
		'curpage' => $tl['page'],
		'pages' => $tl['pages'],
		'search' => $search));
}

# Details for a single tag -> /tags/:id/:page
# @deprecated since 0.9.3
function tag($id) {
	global $app, $globalSettings, $bbs;

	$details = $bbs->tagDetails($id);
	if (is_null($details)) {
		$app->getLog()->debug("no tag");
		$app->notFound();		
	}
	$app->render('tag_detail.html',array(
		'page' => mkPage(getMessageString('tag_details')), 
		'tag' => $details['tag'], 
		'books' => $details['books']));
}

/**
 * Details for a single tag -> /tags/:id/:page/
 * Shows the detail data for the tag plus a paginated list of books
 * 
 * @param  integer $id    series id
 * @param  integer $index page index for books
 * @return HTML page
 */
function tagDetailsSlice ($id, $index=0) {
  global $app, $globalSettings, $bbs;

	$app->getLog()->debug('tagDetailsSlice started with index '.$index);
	$tl = $bbs->tagDetailsSlice($id, $index, $globalSettings[PAGE_SIZE]);
	if (is_null($tl)) {
		$app->getLog()->debug('no tag '.$id);
		$app->notFound();		
	}
	foreach ($tl['entries'] as $book)
		$book->formats = $bbs->titleGetFormats($book->id);
	$app->render('tag_detail.html',array(
		'page' => mkPage(getMessageString('tag_details')),
    	'url' => 'tags/'.$id, 
		'tag' => $tl['tag'], 
		'books' => $tl['entries'],
    	'curpage' => $tl['page'],
    	'pages' => $tl['pages']));   
}

/*********************************************************************
 * OPDS Catalog functions
 ********************************************************************/

function opdsCheckConfig() {
	global $we_have_config, $app;

	$result = check_config();
	if ($result != 0) {
		$app->getLog()->error('opdsCheckConfig: Configuration invalid, check config error '.$result);	
		$app->response()->status(500);
		$app->response()->header('Content-type','text/html');
		$app->response()->body('<p>BucBucStriim: Invalid Configuration.</p>');
	}
}

/**
 * Generate and send the OPDS root navigation catalog
 */
function opdsRoot() {
	global $app, $appversion, $bbs, $globalSettings;

	$app->getLog()->debug('opdsRoot started');			
	$gen = new OpdsGenerator($app->request()->getRootUri(), $appversion, 
		$bbs->calibre_dir,
		date(DATE_ATOM,$bbs->calibre_last_modified),
		$globalSettings['l10n']);
	$cat = $gen->rootCatalog(NULL);	
	$app->response()->status(200);
	$app->response()->header('Content-Type',OpdsGenerator::OPDS_MIME_NAV);
	$app->response()->header('Content-Length',strlen($cat));
	$app->response()->body($cat);
	$app->getLog()->debug('opdsRoot ended');			
}

/**
 * Generate and send the OPDS 'newest' catalog. This catalog is an
 * acquisition catalog with a subset of the title details.
 *
 * Note: OPDS acquisition feeds need an acquisition link for every item,
 * so books without formats are removed from the output.
 */
function opdsNewest() {
	global $app, $appversion, $bbs, $globalSettings;

	$app->getLog()->debug('opdsNewest started');			
	$just_books = $bbs->last30Books();
	$app->getLog()->debug('opdsNewest: 30 books found');			
	$books = array();
	foreach ($just_books as $book) {
		$record = $bbs->titleDetailsOpds($book);
		if (!empty($record['formats']))
			array_push($books,$record);
	}
	$app->getLog()->debug('opdsNewest: details found');			
	$gen = new OpdsGenerator($app->request()->getRootUri(), $appversion, 
		$bbs->calibre_dir,
		date(DATE_ATOM,$bbs->calibre_last_modified),
		$globalSettings['l10n']);
	$app->response()->status(200);
	$app->response()->header('Content-type',OpdsGenerator::OPDS_MIME_ACQ);
	$gen->newestCatalog('php://output', $books, false);
	$app->getLog()->debug('opdsNewest ended');			
}

/**
 * Return a page of the titles. 
 * 
 * Note: OPDS acquisition feeds need an acquisition link for every item,
 * so books without formats are removed from the output.
 * 
 * @param  integer $index=0 page index
 */
function opdsByTitle($index=0) {
	global $app, $appversion, $bbs, $globalSettings;

	$app->getLog()->debug('opdsByTitle started, showing page '.$index);			
	$search = $app->request()->get('search');
	if (isset($search))
		$tl = $bbs->titlesSlice($index,$globalSettings[PAGE_SIZE],$search);
	else
		$tl = $bbs->titlesSlice($index,$globalSettings[PAGE_SIZE]);
	$app->getLog()->debug('opdsByTitle: books found');			
	$books = $bbs->titleDetailsFilteredOpds($tl['entries']);
	$app->getLog()->debug('opdsByTitle: details found');
	$gen = new OpdsGenerator($app->request()->getRootUri(), $appversion, 
		$bbs->calibre_dir,
		date(DATE_ATOM,$bbs->calibre_last_modified),
		$globalSettings['l10n']);
	$app->response()->status(200);
	$app->response()->header('Content-type',OpdsGenerator::OPDS_MIME_ACQ);
	$gen->titlesCatalog('php://output', $books, is_protected(NULL), 
		$tl['page'], getNextSearchPage($tl), getLastSearchPage($tl));
	$app->getLog()->debug('opdsByTitle ended');			
}

/**
 * Return a page with author names initials
 */
function opdsByAuthorInitial() {
	global $app, $appversion, $bbs, $globalSettings;

	$app->getLog()->debug('opdsByAuthorInitial started');			
	$initials = $bbs->authorsInitials();
	$app->getLog()->debug('opdsByAuthorInitial: initials found');			
	$gen = new OpdsGenerator($app->request()->getRootUri(), $appversion, 
		$bbs->calibre_dir,
		date(DATE_ATOM,$bbs->calibre_last_modified),
		$globalSettings['l10n']);
	$app->response()->status(200);
	$app->response()->header('Content-type',OpdsGenerator::OPDS_MIME_NAV);
	$gen->authorsRootCatalog('php://output', $initials);
	$app->getLog()->debug('opdsByAuthorInitial ended');			
}

/**
 * Return a page with author names for a initial
 */
function opdsByAuthorNamesForInitial($initial) {
	global $app, $appversion, $bbs, $globalSettings;

	$app->getLog()->debug('opdsByAuthorNamesForInitial started, showing initial '.$initial);			
	$authors = $bbs->authorsNamesForInitial($initial);
	$app->getLog()->debug('opdsByAuthorNamesForInitial: initials found');			
	$gen = new OpdsGenerator($app->request()->getRootUri(), $appversion, 
		$bbs->calibre_dir,
		date(DATE_ATOM,$bbs->calibre_last_modified),
		$globalSettings['l10n']);
	$app->response()->status(200);
	$app->response()->header('Content-type',OpdsGenerator::OPDS_MIME_NAV);
	$gen->authorsNamesForInitialCatalog('php://output', $authors, $initial);
	$app->getLog()->debug('opdsByAuthorNamesForInitial ended');			
}

/**
 * Return a feed with partial acquisition entries for the author's books
 * @param  string $initial initial character
 * @param  int 		$id      author id
 */
function opdsByAuthor($initial,$id) {
	global $app, $appversion, $bbs, $globalSettings;

	$app->getLog()->debug('opdsByAuthor started, showing initial '.$initial.', id '.$id);			
	$adetails = $bbs->authorDetails($id);
	$books = $bbs->titleDetailsFilteredOpds($adetails['books']);
	$app->getLog()->debug('opdsByAuthor: details found');			
	$gen = new OpdsGenerator($app->request()->getRootUri(), $appversion, 
		$bbs->calibre_dir,
		date(DATE_ATOM,$bbs->calibre_last_modified),
		$globalSettings['l10n']);
	$app->response()->status(200);
	$app->response()->header('Content-type',OpdsGenerator::OPDS_MIME_ACQ);
	$gen->booksForAuthorCatalog('php://output', $books, $initial, 
		$adetails['author'],is_protected(NULL));
	$app->getLog()->debug('opdsByAuthor ended');				
}

/**
 * Return a page with tag initials
 */
function opdsByTagInitial() {
	global $app, $appversion, $bbs, $globalSettings;

	$app->getLog()->debug('opdsByTagInitial started');			
	$initials = $bbs->tagsInitials();
	$app->getLog()->debug('opdsByTagInitial: initials found');			
	$gen = new OpdsGenerator($app->request()->getRootUri(), $appversion, 
		$bbs->calibre_dir,
		date(DATE_ATOM,$bbs->calibre_last_modified),
		$globalSettings['l10n']);
	$app->response()->status(200);
	$app->response()->header('Content-type',OpdsGenerator::OPDS_MIME_NAV);
	$gen->tagsRootCatalog('php://output', $initials);
	$app->getLog()->debug('opdsByTagInitial ended');			
}

/**
 * Return a page with author names for a initial
 */
function opdsByTagNamesForInitial($initial) {
	global $app, $appversion, $bbs, $globalSettings;

	$app->getLog()->debug('opdsByTagNamesForInitial started, showing initial '.$initial);			
	$tags = $bbs->tagsNamesForInitial($initial);
	$app->getLog()->debug('opdsByTagNamesForInitial: initials found');			
	$gen = new OpdsGenerator($app->request()->getRootUri(), $appversion, 
		$bbs->calibre_dir,
		date(DATE_ATOM,$bbs->calibre_last_modified),
		$globalSettings['l10n']);
	$app->response()->status(200);
	$app->response()->header('Content-type',OpdsGenerator::OPDS_MIME_NAV);
	$gen->tagsNamesForInitialCatalog('php://output', $tags, $initial);
	$app->getLog()->debug('opdsByTagNamesForInitial ended');			
}

/**
 * Return a feed with partial acquisition entries for the tags's books
 * @param  string $initial initial character
 * @param  int 		$id      tag id
 */
function opdsByTag($initial,$id) {
	global $app, $appversion, $bbs, $globalSettings;

	$app->getLog()->debug('opdsByTag started, showing initial '.$initial.', id '.$id);			
	$adetails = $bbs->tagDetails($id);
	$books = $bbs->titleDetailsFilteredOpds($adetails['books']);
	$app->getLog()->debug('opdsByTag: details found');			
	$gen = new OpdsGenerator($app->request()->getRootUri(), $appversion, 
		$bbs->calibre_dir,
		date(DATE_ATOM,$bbs->calibre_last_modified),
		$globalSettings['l10n']);
	$app->response()->status(200);
	$app->response()->header('Content-type',OpdsGenerator::OPDS_MIME_ACQ);
	$gen->booksForTagCatalog('php://output', $books, $initial, 
		$adetails['tag'],is_protected(NULL));
	$app->getLog()->debug('opdsByTag ended');				
}

/**
 * Return a page with series initials
 */
function opdsBySeriesInitial() {
	global $app, $appversion, $bbs, $globalSettings;

	$app->getLog()->debug('opdsBySeriesInitial started');			
	$initials = $bbs->seriesInitials();
	$app->getLog()->debug('opdsBySeriesInitial: initials found');			
	$gen = new OpdsGenerator($app->request()->getRootUri(), $appversion, 
		$bbs->calibre_dir,
		date(DATE_ATOM,$bbs->calibre_last_modified),
		$globalSettings['l10n']);
	$app->response()->status(200);
	$app->response()->header('Content-type',OpdsGenerator::OPDS_MIME_NAV);
	$gen->seriesRootCatalog('php://output', $initials);
	$app->getLog()->debug('opdsBySeriesInitial ended');			
}

/**
 * Return a page with author names for a initial
 */
function opdsBySeriesNamesForInitial($initial) {
	global $app, $appversion, $bbs, $globalSettings;

	$app->getLog()->debug('opdsBySeriesNamesForInitial started, showing initial '.$initial);			
	$tags = $bbs->seriesNamesForInitial($initial);
	$app->getLog()->debug('opdsBySeriesNamesForInitial: initials found');			
	$gen = new OpdsGenerator($app->request()->getRootUri(), $appversion, 
		$bbs->calibre_dir,
		date(DATE_ATOM,$bbs->calibre_last_modified),
		$globalSettings['l10n']);
	$app->response()->status(200);
	$app->response()->header('Content-type',OpdsGenerator::OPDS_MIME_NAV);
	$gen->seriesNamesForInitialCatalog('php://output', $tags, $initial);
	$app->getLog()->debug('opdsBySeriesNamesForInitial ended');			
}

/**
 * Return a feed with partial acquisition entries for the series' books
 * @param  string $initial initial character
 * @param  int 		$id      tag id
 */
function opdsBySeries($initial,$id) {
	global $app, $appversion, $bbs, $globalSettings;

	$app->getLog()->debug('opdsBySeries started, showing initial '.$initial.', id '.$id);			
	$adetails = $bbs->seriesDetails($id);
	$books = $bbs->titleDetailsFilteredOpds($adetails['books']);
	$app->getLog()->debug('opdsBySeries: details found');			
	$gen = new OpdsGenerator($app->request()->getRootUri(), $appversion, 
		$bbs->calibre_dir,
		date(DATE_ATOM,$bbs->calibre_last_modified),
		$globalSettings['l10n']);
	$app->response()->status(200);
	$app->response()->header('Content-type',OpdsGenerator::OPDS_MIME_ACQ);
	$gen->booksForSeriesCatalog('php://output', $books, $initial, 
		$adetails['series'],is_protected(NULL));
	$app->getLog()->debug('opdsBySeries ended');				
}

/**
 * Format and send the OpenSearch descriptor document
 */
function opdsSearchDescriptor() {
	global $app, $appversion, $bbs, $globalSettings;	

	$app->getLog()->debug('opdsSearchDescriptor started');		
	$gen = new OpdsGenerator($app->request()->getRootUri(), $appversion, 
		$bbs->calibre_dir,
		date(DATE_ATOM,$bbs->calibre_last_modified),
		$globalSettings['l10n']);
	$app->response()->status(200);
	$app->response()->header('Content-type',OpdsGenerator::OPENSEARCH_MIME);
	$gen->searchDescriptor('php://output', '/opds/searchlist/0/');
	$app->getLog()->debug('opdsSearchDescriptor ended');				
}

/**
 * Create and send the catalog page for the current search criteria. 
 * The search criteria is a GET paramter string.
 * 
 * @param  integer $index index of page in search
 */
function opdsBySearch($index=0) {
	global $app, $appversion, $bbs, $globalSettings;

	$app->getLog()->debug('opdsBySearch started, showing page '.$index);			
	$search = $app->request()->get('search');
	if (!isset($search)) {
		$app->getLog()->error('opdsBySearch called without search criteria, page '.$index);			
		// 400 Bad request
		$app->response()->status(400);
		return;
	}	
	$app->getLog()->debug('opdsBySearch search '.$search);			
	$tl = $bbs->titlesSlice($index,$globalSettings[PAGE_SIZE],$search);	
	$app->getLog()->debug('opdsBySearch: books found');			
	$books = $bbs->titleDetailsFilteredOpds($tl['entries']);
	$app->getLog()->debug('opdsBySearch: details found');	
	$gen = new OpdsGenerator($app->request()->getRootUri(), $appversion, 
		$bbs->calibre_dir,
		date(DATE_ATOM,$bbs->calibre_last_modified),
		$globalSettings['l10n']);
	$app->response()->status(200);
	$app->response()->header('Content-type',OpdsGenerator::OPDS_MIME_ACQ);
	$gen->searchCatalog('php://output', $books, is_protected(NULL), 
		$tl['page'], getNextSearchPage($tl), getLastSearchPage($tl), $search, 
		$tl['total'], $globalSettings[PAGE_SIZE]);
	$app->getLog()->debug('opdsBySearch ended');			
}

/*********************************************************************
 * Utility and helper functions, private
 ********************************************************************/


/**
 * Check whether the book download must be protected. 
 *
 * If libmcrypt is available, encrypted cookies are used.
 * 
 * @param  int  		$id book id
 * @return boolean  true - the user must enter a password, else no authentication necessary
 */
function is_protected($id) {
	global $app, $calibre_dir, $globalSettings, $bbs;
	$protected = false;

	$pw = getDownloadPassword();
	if (!is_null($pw)) {
		$glob_dl_cookie = getOurCookie(GLOBAL_DL_COOKIE);
		$app->getLog()->debug('is_protected: download protection enabled, cookie: '.$glob_dl_cookie);
		
		//check if tag selection is active
		$tgc 	= $globalSettings[TAG_PROTECT_CHOICE];
		if ($tgc == 0) { // No selective protection by tags
			$app->getLog()->debug('is_protected: using global download protection');		
			$protected = checkPWCookie($glob_dl_cookie, $pw);
		} else { // tag selection is active, check if control is ok
			$app->getLog()->debug('is_protected: using selective download protection');		
			$has_protective_tag = isProtectedByTag($id);
			if ($has_protective_tag) {
				$protected = checkPWCookie($glob_dl_cookie, $pw);
			} else {
				$protected = false;
			}
		}
	} else {
		$app->getLog()->debug('is_protected: download protection disabled');		
		$protected = false;
	}
	$app->getLog()->debug('is_protected: result '+$protected);		
	return $protected;
}

function checkPWCookie($cookie, $pw) {
	if (is_null($cookie))
		return true;
	else {
		if ($cookie === $pw)
			return false;
		else
			return true;
	}
}

# Utility to check whether the book with ID $id contains a tag, that is marked as protected
function isProtectedByTag($id) {
	global $globalSettings, $bbs;

	//field need to check for tag protection
	$ctags	= $globalSettings[TAG_PROTECT_FIELD];
	
	$details = $bbs->titleDetails($id);
	$allTags = $details['tags'];
	
	//search if tag is the same as in admin settings
	$ct = false;
	foreach ($allTags as &$aTag) {
		$tagName = $aTag->name;			
		if ($tagName == $ctags){
			$ct = true;
		}
	}
	return $ct;
}

# Utility function to fill the page array
function mkPage($subtitle='', $menu=0, $dialog=false) {
	global $app, $globalSettings;

	if ($subtitle == '') 
		$title = $globalSettings[DISPLAY_APP_NAME];
	else
		$title = $globalSettings[DISPLAY_APP_NAME].$globalSettings['sep'].$subtitle;
	$rot = $app->request()->getRootUri();
	$page = array('title' => $title, 
		'rot' => $rot,
		'h1' => $subtitle,
		'version' => $globalSettings['version'],
		'glob' => $globalSettings,
		'menu' => $menu,
		'dialog' => $dialog);
	return $page;
}

/**
 * Return the admin password or NULL if none is set.
 * @return string admin password or NULL
 */
function getAdminPassword() {
	global $globalSettings;

	if (empty($globalSettings[ADMIN_PW])) 
		return NULL;
	else
		return $globalSettings[ADMIN_PW];
}

/**
 * Return the download password or NULL if no download protection is set.
 * @return string download password or NULL
 */
function getDownloadPassword() {
	global $globalSettings;

	if ($globalSettings[GLOB_DL_CHOICE] == "1") 
		$cpw = $globalSettings[ADMIN_PW];
	elseif ($globalSettings[GLOB_DL_CHOICE] == "2") 
		$cpw = $globalSettings[GLOB_DL_PASSWORD];
	else
		$cpw = NULL;
	return $cpw;
}

/**
 * Return a localized message string for $id. 
 *
 * If there is no defined message for $id in the current language the function
 * looks for an alterantive in English. If that also fails an error message 
 * is returned.
 * 
 * @param  string $id message id
 * @return string     localized message string
 */
function getMessageString($id) {
	global $app, $globalSettings;
	#$msg = $globalSettings['langa'][$id];
	$msg = $globalSettings['l10n']->message($id);
	return $msg;
}

/**
 * Calcluate the next page number for search results
 * @param  array $tl search result
 * @return int       page index or NULL
 */
function getNextSearchPage($tl) {
	if ($tl['page'] < $tl['pages']-1)
		$nextPage = $tl['page']+1;
	else
		$nextPage = NULL;
	return $nextPage;
}

/**
 * Caluclate the last page numberfor search results
 * @param  array $tl 	search result
 * @return int     		page index
 */
function getLastSearchPage($tl) {
	if ($tl['pages'] == 0)
		$lastPage = 0;
	else
		$lastPage = $tl['pages']-1;	
	return $lastPage;
}

/**
 * Get the value of the cookie
 * @param string $name 	cookie name
 * @return string 			cookie value or NULL if not available
 */
function getOurCookie($name) {
	global $app, $globalSettings;	
	if ($globalSettings['crypt'] == true) {
		$cookie = $app->getEncryptedCookie($name);	
	} else {
		$cookie = $app->getCookie($name);
	}
	return $cookie;
}

/**
 * Set a cookie
 * @param string $name 	cookie name
 * @param string $value cookie value
 */
function setOurCookie($name, $value) {
	global $app, $globalSettings;	
	if ($globalSettings['crypt'] == true) {
		$cookie = $app->setEncryptedCookie($name, $value);	
	} else {
		$cookie = $app->setCookie($name, $value);
	}
}


/**
 * Returns the user language, priority:
 * 1. Language in $_GET['lang']
 * 2. Language in $_SESSION['lang']
 * 3. HTTP_ACCEPT_LANGUAGE
 * 4. Fallback language
 *
 * @return the user language, like 'de' or 'en'
 */
function getUserLang($allowedLangs, $fallbackLang) {
  // reset user_lang array
  $userLangs = array();
  // 2nd highest priority: GET parameter 'lang'
  if(isset($_GET['lang']) && is_string($_GET['lang'])) {
      $userLangs[] =  $_GET['lang'];
  }
	// 3rd highest priority: SESSION parameter 'lang'
  if(isset($_SESSION['lang']) && is_string($_SESSION['lang'])) {
      $userLangs[] = $_SESSION['lang'];
  }
  // 4th highest priority: HTTP_ACCEPT_LANGUAGE
  if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    foreach (explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']) as $part) {
      $userLangs[] = strtolower(substr($part,0,2));
    }
  }
  // Lowest priority: fallback
  $userLangs[] = $fallbackLang;    
  foreach($allowedLangs as $al) {
  	if ($userLangs[0] == $al)
  		return $al;
  }
  return $fallbackLang;
}

# Utility function to serve files
function readfile_chunked($filename) {
	global $app;
	$app->getLog()->debug('readfile_chunked '.$filename);
	$buffer = '';
	$handle = fopen($filename, 'rb');
	if ($handle === false) {
		return false;
	}
	while (!feof($handle)) {
		$buffer = fread($handle, 1024*1024);
		echo $buffer;
		ob_flush();
		flush();
	}
	$status = fclose($handle);
	return $status;
	
}
# Check for valid email address format
function isEMailValid($mail) {
	return (filter_var($mail, FILTER_VALIDATE_EMAIL) !== FALSE);
}


?>
