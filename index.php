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
require_once 'lib/BicBucStriim/login_middleware.php';
require_once 'vendor/email.php';

# Allowed languages, i.e. languages with translations
$allowedLangs = array('de','en','fr','nl');
# Fallback language if the browser prefers other than the allowed languages
$fallbackLang = 'en';
# Application Name
$appname = 'BicBucStriim';
# App version
$appversion = '1.2.0-𝛂';
# Current DB schema version
define('DB_SCHEMA_VERSION', '3');

# URL for version information
define('VERSION_URL', 'http://projekte.textmulch.de/bicbucstriim/version.json');
# Cookie name to store Kindle email address
define('KINDLE_COOKIE', 'kindle_email');
# Calibre library path
define('CALIBRE_DIR', 'calibre_dir');
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
$globalSettings[CALIBRE_DIR] = '';
$globalSettings[DB_VERSION] = DB_SCHEMA_VERSION;
$globalSettings[KINDLE] = 0;
$globalSettings[KINDLE_FROM_EMAIL] = '';
$globalSettings[THUMB_GEN_CLIPPED] = 1;
$globalSettings[PAGE_SIZE] = 30;
$globalSettings[DISPLAY_APP_NAME] = $appname;

$knownConfigs = array(CALIBRE_DIR, DB_VERSION, KINDLE, KINDLE_FROM_EMAIL, 
	THUMB_GEN_CLIPPED, PAGE_SIZE, DISPLAY_APP_NAME);

$bbs = new BicBucStriim();
$app->bbs = $bbs;
$app->add(new \CalibreConfigMiddleware(CALIBRE_DIR));
$app->add(new \LoginMiddleware($appname, array('js', 'img', 'style')));
$app->add(new \OwnConfigMiddleware($knownConfigs));

###### Init routes for production
$app->notFound('myNotFound');
$app->get('/', 'main');
$app->get('/admin/', 'admin');
$app->get('/admin/configuration/', 'admin_configuration');
$app->post('/admin/configuration/', 'admin_change_json');
$app->get('/admin/users/', 'admin_get_users');
$app->post('/admin/users/', 'admin_add_user');
$app->get('/admin/users/:id/', 'admin_get_user');
$app->put('/admin/users/:id/', 'admin_modify_user');
$app->delete('/admin/users/:id/', 'admin_delete_user');
$app->get('/admin/version/', 'admin_check_version');
$app->get('/authors/:id/:page/', 'authorDetailsSlice');
$app->get('/authorslist/:id/', 'authorsSlice');
$app->get('/login/', 'show_login');
$app->post('/login/', 'perform_login');
$app->get('/logout/', 'logout');
$app->get('/search/', 'globalSearch');
$app->get('/series/:id/:page/', 'seriesDetailsSlice');
$app->get('/serieslist/:id/', 'seriesSlice');
$app->get('/tags/:id/:page/', 'tagDetailsSlice');
$app->get('/tagslist/:id/', 'tagsSlice');
$app->get('/titles/:id/', 'title');
$app->get('/titles/:id/cover/', 'cover');
$app->get('/titles/:id/file/:file', 'book');
$app->post('/titles/:id/kindle/:file', 'kindle');
$app->get('/titles/:id/thumbnail/', 'thumbnail');
$app->get('/titleslist/:id/', 'titlesSlice');
$app->get('/opds/', 'opdsRoot');
$app->get('/opds/newest/', 'opdsNewest');
$app->get('/opds/titleslist/:id/', 'opdsByTitle');
$app->get('/opds/authorslist/', 'opdsByAuthorInitial');
$app->get('/opds/authorslist/:initial/', 'opdsByAuthorNamesForInitial');
$app->get('/opds/authorslist/:initial/:id/', 'opdsByAuthor');
$app->get('/opds/tagslist/',  'opdsByTagInitial');
$app->get('/opds/tagslist/:initial/', 'opdsByTagNamesForInitial');
$app->get('/opds/tagslist/:initial/:id/', 'opdsByTag');
$app->get('/opds/serieslist/', 'opdsBySeriesInitial');
$app->get('/opds/serieslist/:initial/', 'opdsBySeriesNamesForInitial');
$app->get('/opds/serieslist/:initial/:id/', 'opdsBySeries');
$app->get('/opds/opensearch.xml', 'opdsSearchDescriptor');
$app->get('/opds/searchlist/:id/', 'opdsBySearch');
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
			$success = $app->strong->login($uname, $upw);
			$app->getLog()->debug('login success: '.var_export($success,true));	
			if($success)
				$app->redirect($app->request->getRootUri());
			else 
				$app->render('login.html', array(
					'page' => mkPage(getMessageString('login')))); 			
		}
	} else {
		$app->render('login.html', array(
			'page' => mkPage(getMessageString('login')))); 			
	}
}

function logout() {
	global $app, $globalSettings;
	if ($app->strong->loggedIn())
		$app->strong->logout();
	$app->render('logout.html', array(
		'page' => mkPage(getMessageString('logout')))); 
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
	global $app;

	$app->render('admin.html',array(
		'page' => mkPage(getMessageString('admin')),
		'isadmin' => is_admin()));
}


/**
 * Generate the configuration page -> GET /admin/configuration/
 */
function admin_configuration() {
	global $app;

	$app->render('admin_configuration.html',array(
		'page' => mkPage(getMessageString('admin')),
		'isadmin' => is_admin()));
}

/**
 * Generate the users overview page -> GET /admin/users/
 */
function admin_get_users() {
	global $app;

	$users = $app->bbs->users();
	$app->render('admin_users.html',array(
		'page' => mkPage(getMessageString('admin_users')),
		'users' => $users,
		'isadmin' => is_admin()));
}

/**
 * Generate the single user page -> GET /admin/users/:id/
 */
function admin_get_user($id) {
	global $app;

	$user = $app->bbs->user($id);
	$app->getLog()->debug('admin_get_user: '.var_export($user, true));	
	$app->render('admin_user.html',array(
		'page' => mkPage(getMessageString('admin_users')),
		'user' => $user,
		'isadmin' => is_admin()));
}

/**
 * Add a user -> POST /admin/users/ (JSON)
 */
function admin_add_user() {
	global $app;

	$user_data = $app->request()->post();
	$app->getLog()->debug('admin_add_user: '.var_export($user_data, true));	
	$user = $app->bbs->addUser($user_data['username'], $user_data['password']);
	$resp = $app->response();
	if (isset($user) && !is_null($user)) {
		$resp->status(200);
		$msg = getMessageString('admin_modified');
	} else {
		$resp->status(500);
		$msg = getMessageString('admin_modify_error');
	}
	$answer = json_encode(array('user' => $user, 'msg' => $msg));
	$resp->header('Content-type','application/json');
	$resp->header('Content-Length',strlen($answer));
	$resp->body($answer);
}

/**
 * Delete a user -> DELETE /admin/users/:id/ (JSON)
 */
function admin_delete_user($id) {
	global $app;

	$app->getLog()->debug('admin_delete_user: '.var_export($id, true));	
	$success = $app->bbs->deleteUser($id);
	$resp = $app->response();
	if ($success) {
		$resp->status(200);
		$msg = getMessageString('admin_modified');
	} else {
		$resp->status(500);
		$msg = getMessageString('admin_modify_error');
	}
	$answer = json_encode(array('msg' => $msg));
	$resp->header('Content-type','application/json');
	$resp->header('Content-Length',strlen($answer));
	$resp->body($answer);
}

/**
 * Modify a user -> PUT /admin/users/:id/ (JSON)
 */
function admin_modify_user($id) {
	global $app;

	$user_data = $app->request()->put();
	$app->getLog()->debug('admin_modify_user: '.var_export($user_data, true));	
	$user = $app->bbs->changeUser($id, $user_data['password'], 
		$user_data['languages'], $user_data['tags']);
	$resp = $app->response();
	if (isset($user) && !is_null($user)) {
		$resp->status(200);
		$msg = getMessageString('admin_modified');
	} else {
		$resp->status(500);
		$msg = getMessageString('admin_modify_error');
	}
	$answer = json_encode(array('user' => $user, 'msg' => $msg));
	$resp->header('Content-type','application/json');
	$resp->header('Content-Length',strlen($answer));
	$resp->body($answer);
}


/**
 * Processes changes in the admin page -> POST /admin/configuration/
 */
function admin_change_json() {
	global $app, $globalSettings, $bbs;
	$app->getLog()->debug('admin_change: started');	
	# Check access permission
	if (!is_admin()) {
		$app->getLog()->warn('admin_change: no admin permission');	
		$app->render('admin_configuration.html',array(
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
		$app->render('admin_configuration.html',array(
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
		$app->render('admin_configuration.html',array(
			'page' => mkPage(getMessageString('admin')), 
			'messages' => array(getMessageString('changes_saved')),
			'isadmin' => true,
			));	
	}
}

/**
 * Get the new version info and compare it to our version -> GET /admin/version/
 */
function admin_check_version() {
	global $app, $globalSettings;	
	$versionAnswer = array();
	$contents = file_get_contents(VERSION_URL);		
	if ($contents == false) {
		$versionClass = 'error';
		$versionAnswer = sprintf(getMessageString('admin_new_version_error'),$globalSettings['version']);
	} else {
		$versionInfo = json_decode($contents);	
		$version = $globalSettings['version'];
		if (strpos($globalSettings['version'], '-') === false) {
			$v = preg_split('/-/', $globalSettings['version']);
			$version = $v[0];
		} 
		$result = version_compare($version, $versionInfo->{'version'});
		if ($result === -1) {
			$versionClass = 'success';
			$msg1 = sprintf(getMessageString('admin_new_version'),$versionInfo->{'version'},$globalSettings['version']);
			$msg2 = sprintf("<a href=\"%s\">%s</a>",$versionInfo->{'url'},$versionInfo->{'url'});
			$msg3 = sprintf(getMessageString('admin_check_url'),$msg2);
			$versionAnswer = $msg1.'. '.$msg3; 
		} else {
			$versionClass = '';
			$versionAnswer = sprintf(getMessageString('admin_no_new_version'),$globalSettings['version']);
		}		
	}
	$app->render('admin_version.html',array(
			'page' => mkPage(getMessageString('admin_check_version')), 
			'versionClass' => $versionClass,
			'versionAnswer' => $versionAnswer,
			'isadmin' => true,
			));	
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
			'protect_dl' => false)
	);
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


/**
 * Generate and send the OPDS root navigation catalog
 */
function opdsRoot() {
	global $app;

	$gen = mkOpdsGenerator($app);	
	$cat = $gen->rootCatalog(NULL);	
	mkOpdsResponse($app, $cat, OpdsGenerator::OPDS_MIME_NAV);
}

/**
 * Generate and send the OPDS 'newest' catalog. This catalog is an
 * acquisition catalog with a subset of the title details.
 *
 * Note: OPDS acquisition feeds need an acquisition link for every item,
 * so books without formats are removed from the output.
 */
function opdsNewest() {
	global $app;

	$just_books = $app->bbs->last30Books();
	$books = array();
	foreach ($just_books as $book) {
		$record = $app->bbs->titleDetailsOpds($book);
		if (!empty($record['formats']))
			array_push($books,$record);
	}
	$gen = mkOpdsGenerator($app);	
	$cat = $gen->newestCatalog(NULL, $books, false);
	mkOpdsResponse($app, $cat, OpdsGenerator::OPDS_MIME_ACQ);
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
	global $app, $globalSettings;

	$search = $app->request()->get('search');
	if (isset($search))
		$tl = $app->bbs->titlesSlice($index,$globalSettings[PAGE_SIZE], $search);
	else
		$tl = $app->bbs->titlesSlice($index,$globalSettings[PAGE_SIZE]);
	$books = $app->bbs->titleDetailsFilteredOpds($tl['entries']);
	$gen = mkOpdsGenerator($app);	
	$cat = $gen->titlesCatalog(NULL, $books, false, 
		$tl['page'], getNextSearchPage($tl), getLastSearchPage($tl));
	mkOpdsResponse($app, $cat, OpdsGenerator::OPDS_MIME_ACQ);
}

/**
 * Return a page with author names initials
 */
function opdsByAuthorInitial() {
	global $app;

	$initials = $app->bbs->authorsInitials();
	$gen = mkOpdsGenerator($app);	
	$cat = $gen->authorsRootCatalog(NULL, $initials);
	mkOpdsResponse($app, $cat, OpdsGenerator::OPDS_MIME_NAV);
}

/**
 * Return a page with author names for a initial
 */
function opdsByAuthorNamesForInitial($initial) {
	global $app;

	$authors = $app->bbs->authorsNamesForInitial($initial);
	$gen = mkOpdsGenerator($app);	
	$cat = $gen->authorsNamesForInitialCatalog(NULL, $authors, $initial);
	mkOpdsResponse($app, $cat, OpdsGenerator::OPDS_MIME_NAV);
}

/**
 * Return a feed with partial acquisition entries for the author's books
 * @param  string $initial initial character
 * @param  int 		$id      author id
 */
function opdsByAuthor($initial,$id) {
	global $app;

	$adetails = $app->bbs->authorDetails($id);
	$books = $app->bbs->titleDetailsFilteredOpds($adetails['books']);
	$gen = mkOpdsGenerator($app);	
	$cat = $gen->booksForAuthorCatalog(NULL, $books, $initial, 
		$adetails['author'], false);
	mkOpdsResponse($app, $cat, OpdsGenerator::OPDS_MIME_ACQ);
}

/**
 * Return a page with tag initials
 */
function opdsByTagInitial() {
	global $app;

	$initials = $app->bbs->tagsInitials();
	$gen = mkOpdsGenerator($app);	
	$cat = $gen->tagsRootCatalog(NULL, $initials);
	mkOpdsResponse($app, $cat, OpdsGenerator::OPDS_MIME_NAV);
}

/**
 * Return a page with author names for a initial
 */
function opdsByTagNamesForInitial($initial) {
	global $app;

	$tags = $app->bbs->tagsNamesForInitial($initial);
	$gen = mkOpdsGenerator($app);	
	$cat = $gen->tagsNamesForInitialCatalog(NULL, $tags, $initial);
	mkOpdsResponse($app, $cat, OpdsGenerator::OPDS_MIME_NAV);
}

/**
 * Return a feed with partial acquisition entries for the tags's books
 * @param  string $initial initial character
 * @param  int 		$id      tag id
 */
function opdsByTag($initial,$id) {
	global $app;

	$adetails = $app->bbs->tagDetails($id);
	$books = $app->bbs->titleDetailsFilteredOpds($adetails['books']);
	$gen = mkOpdsGenerator($app);	
	$cat = $gen->booksForTagCatalog(NULL, $books, $initial, 
		$adetails['tag'],false);
	mkOpdsResponse($app, $cat, OpdsGenerator::OPDS_MIME_ACQ);
}

/**
 * Return a page with series initials
 */
function opdsBySeriesInitial() {
	global $app;

	$initials = $app->bbs->seriesInitials();
	$gen = mkOpdsGenerator($app);	
	$cat = $gen->seriesRootCatalog(NULL, $initials);
	mkOpdsResponse($app, $cat, OpdsGenerator::OPDS_MIME_NAV);
}

/**
 * Return a page with author names for a initial
 */
function opdsBySeriesNamesForInitial($initial) {
	global $app;

	$tags = $app->bbs->seriesNamesForInitial($initial);
	$gen = mkOpdsGenerator($app);	
	$cat = $gen->seriesNamesForInitialCatalog(NULL, $tags, $initial);
	mkOpdsResponse($app, $cat, OpdsGenerator::OPDS_MIME_NAV);
}

/**
 * Return a feed with partial acquisition entries for the series' books
 * @param  string $initial initial character
 * @param  int 		$id      tag id
 */
function opdsBySeries($initial,$id) {
	global $app;

	$adetails = $app->bbs->seriesDetails($id);
	$books = $app->bbs->titleDetailsFilteredOpds($adetails['books']);
	$gen = mkOpdsGenerator($app);	
	$cat = $gen->booksForSeriesCatalog(NULL, $books, $initial, 
		$adetails['series'], false);
	mkOpdsResponse($app, $cat, OpdsGenerator::OPDS_MIME_ACQ);
}

/**
 * Format and send the OpenSearch descriptor document
 */
function opdsSearchDescriptor() {
	global $app;	

	$gen = mkOpdsGenerator($app);
	$cat = $gen->searchDescriptor(NULL, '/opds/searchlist/0/');
	mkOpdsResponse($app, $cat, OpdsGenerator::OPENSEARCH_MIME);	
}

/**
 * Create and send the catalog page for the current search criteria. 
 * The search criteria is a GET paramter string.
 * 
 * @param  integer $index index of page in search
 */
function opdsBySearch($index=0) {
	global $app, $globalSettings;

	$search = $app->request()->get('search');
	if (!isset($search)) {
		$app->getLog()->error('opdsBySearch called without search criteria, page '.$index);			
		// 400 Bad request
		$app->response()->status(400);
		return;
	}	
	$tl = $app->bbs->titlesSlice($index,$globalSettings[PAGE_SIZE], $search);	
	$books = $app->bbs->titleDetailsFilteredOpds($tl['entries']);
	$gen = mkOpdsGenerator($app);
	$cat = $gen->searchCatalog(NULL, $books, false, 
		$tl['page'], getNextSearchPage($tl), getLastSearchPage($tl), $search, 
		$tl['total'], $globalSettings[PAGE_SIZE]);
	mkOpdsResponse($app, $cat, OpdsGenerator::OPDS_MIME_ACQ);	
}

/*********************************************************************
 * Utility and helper functions, private
 ********************************************************************/


function mkOpdsGenerator($app) {
	global $appversion, $globalSettings;
	$gen = new OpdsGenerator($app->request()->getRootUri(), $appversion, 
		$app->bbs->calibre_dir,
		date(DATE_ATOM, $app->bbs->calibre_last_modified),
		$globalSettings['l10n']);
	return $gen;
}

# Create and send the typical OPDS response
function mkOpdsResponse($app, $content, $type) {
	$resp = $app->response();
	$resp->status(200);
	$resp->header('Content-type', $type);
	$resp->header('Content-Length',strlen($content));	
	$resp->body($content);	
}

# Utility function to fill the page array
function mkPage($subtitle='', $menu=0, $dialog=false) {
	global $app, $globalSettings;

	if ($subtitle == '') 
		$title = $globalSettings[DISPLAY_APP_NAME];
	else
		$title = $globalSettings[DISPLAY_APP_NAME].$globalSettings['sep'].$subtitle;
	$rot = $app->request()->getRootUri();
	$auth = $app->strong->loggedIn();
	$page = array('title' => $title, 
		'rot' => $rot,
		'h1' => $subtitle,
		'version' => $globalSettings['version'],
		'glob' => $globalSettings,
		'menu' => $menu,
		'dialog' => $dialog,
		'auth' => $auth,
		'admin' => is_admin());
	return $page;
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
 * Check for admin permissions. Currently this is only the user 
 * <em>admin</em>, ID 1.
 */
function is_admin() {
	global $app;
	if ($app->strong->loggedIn()) {
		$user = $app->strong->getUser();
		return ($user['id'] === '1');
	} else {
		return false;
	}
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
