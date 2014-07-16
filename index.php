<?php
/**
 * BicBucStriim
 *
 * Copyright 2012-2013 Rainer Volz
 * Licensed under MIT License, see LICENSE
 * 
 */

require 'vendor/autoload.php';
set_include_path(get_include_path() . PATH_SEPARATOR . './lib/BicBucStriim');
set_include_path(get_include_path() . PATH_SEPARATOR . './vendor');
require 'rb.php';
require_once 'langs.php';
require_once 'l10n.php';
require_once 'app_constants.php';
require_once 'bicbucstriim.php';
require_once 'calibre.php';
require_once 'opds_generator.php';
require_once 'own_config_middleware.php';
require_once 'calibre_config_middleware.php';
require_once 'login_middleware.php';
require_once 'caching_middleware.php';
require_once 'mailer.php';
require_once 'metadata_epub.php';

use dflydev\markdown\MarkdownExtraParser;

# Allowed languages, i.e. languages with translations
$allowedLangs = array('de','en','fr','it','nl');
# Fallback language if the browser prefers other than the allowed languages
$fallbackLang = 'en';
# Application Name
$appname = 'BicBucStriim';
# App version
$appversion = '1.2.3';

# Init app and routes
$app = new \Slim\Slim(array(
	'view' => new \Slim\Views\Twig(),
	'mode' => 'production',
	#'mode' => 'debug',
	#'mode' => 'development',
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
	require 'vendor/DateTimeFileWriter.php';
	$app->getLog()->setEnabled(true);
	$app->getLog()->setLevel(\Slim\Log::DEBUG);
	$app->getLog()->setWriter(new \Slim\Extras\Log\DateTimeFileWriter(array('path' => './data', 'name_format' => 'Y-m-d')));
	$app->getLog()->info($appname.' '.$appversion.': Running in debug mode.');
	error_reporting(E_ALL);
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
$globalSettings[MAILER] = Mailer::MAIL;
$globalSettings[SMTP_USER] = '';
$globalSettings[SMTP_PASSWORD] = '';
$globalSettings[SMTP_SERVER] = '';
$globalSettings[SMTP_PORT] = 25;
$globalSettings[SMTP_ENCRYPTION] = 0;
$globalSettings[METADATA_UPDATE] = 0;

$knownConfigs = array(CALIBRE_DIR, DB_VERSION, KINDLE, KINDLE_FROM_EMAIL, 
	THUMB_GEN_CLIPPED, PAGE_SIZE, DISPLAY_APP_NAME, MAILER, SMTP_SERVER,
	SMTP_PORT, SMTP_USER, SMTP_PASSWORD, SMTP_ENCRYPTION, METADATA_UPDATE);

# Freeze (true) DB schema before release! Set to false for DB development.
$app->bbs = new BicBucStriim('data/data.db', true);
$app->add(new \CalibreConfigMiddleware(CALIBRE_DIR));
$app->add(new \LoginMiddleware($appname, array('js', 'img', 'style')));
$app->add(new \OwnConfigMiddleware($knownConfigs));
$app->add(new \CachingMiddleware(array('/admin', '/login')));

###### Init routes for production
$app->notFound('myNotFound');
$app->get('/', 'main');
$app->get('/admin/', 'check_admin', 'admin');
$app->get('/admin/configuration/', 'check_admin', 'admin_configuration');
$app->post('/admin/configuration/', 'check_admin', 'admin_change_json');
$app->get('/admin/idtemplates/', 'check_admin', 'admin_get_idtemplates');
$app->put('/admin/idtemplates/:id/', 'check_admin', 'admin_modify_idtemplate');
$app->delete('/admin/idtemplates/:id/', 'check_admin', 'admin_clear_idtemplate');
$app->get('/admin/mail/', 'check_admin', 'admin_get_smtp_config');
$app->put('/admin/mail/', 'check_admin', 'admin_change_smtp_config');
$app->get('/admin/users/', 'check_admin', 'admin_get_users');
$app->post('/admin/users/', 'check_admin', 'admin_add_user');
$app->get('/admin/users/:id/', 'check_admin', 'admin_get_user');
$app->put('/admin/users/:id/', 'check_admin', 'admin_modify_user');
$app->delete('/admin/users/:id/', 'check_admin', 'admin_delete_user');
$app->get('/admin/version/', 'check_admin', 'admin_check_version');
$app->get('/authors/:id/notes/', 'check_admin', 'authorNotes');
#$app->post('/authors/:id/notes/', 'check_admin', 'authorNotesEdit');
$app->get('/authors/:id/:page/', 'authorDetailsSlice');
$app->get('/authorslist/:id/', 'authorsSlice');
$app->get('/login/', 'show_login');
$app->post('/login/', 'perform_login');
$app->get('/logout/', 'logout');
$app->post('/metadata/authors/:id/thumbnail/', 'check_admin', 'edit_author_thm');
$app->delete('/metadata/authors/:id/thumbnail/', 'check_admin', 'del_author_thm');
$app->post('/metadata/authors/:id/notes/', 'check_admin', 'edit_author_notes');
$app->delete('/metadata/authors/:id/notes/', 'check_admin', 'del_author_notes');
$app->post('/metadata/authors/:id/links/', 'check_admin', 'new_author_link');
$app->delete('/metadata/authors/:id/links/:link_id/', 'check_admin', 'del_author_link');
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
$app->get('/opds/authorslist/:initial/:id/:page/', 'opdsByAuthor');
$app->get('/opds/tagslist/',  'opdsByTagInitial');
$app->get('/opds/tagslist/:initial/', 'opdsByTagNamesForInitial');
$app->get('/opds/tagslist/:initial/:id/:page/', 'opdsByTag');
$app->get('/opds/serieslist/', 'opdsBySeriesInitial');
$app->get('/opds/serieslist/:initial/', 'opdsBySeriesNamesForInitial');
$app->get('/opds/serieslist/:initial/:id/:page/', 'opdsBySeries');
$app->get('/opds/opensearch.xml', 'opdsSearchDescriptor');
$app->get('/opds/searchlist/:id/', 'opdsBySearch');
$app->run();


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
 * Check admin rights and redirect if necessary
 */
function check_admin() {
	global $app;

	if (!is_admin()) {
		$app->render('error.html',array(
			'page' => mkPage(getMessageString('error'), 0, 0),
			'error' => getMessageString('error_no_access')));		
	}
}


/**
 * Generate the admin page -> /admin/
 */
function admin() {
	global $app;

	$app->render('admin.html',array(
		'page' => mkPage(getMessageString('admin'), 0, 1),
		'isadmin' => is_admin()));
}


function mkMailers() {
	$e0 = new ConfigMailer();
	$e0->key = Mailer::SMTP;
	$e0->text = getMessageString('admin_mailer_smtp');
	$e1 = new ConfigMailer();
	$e1->key = Mailer::SENDMAIL;
	$e1->text = getMessageString('admin_mailer_sendmail');
	$e2 = new ConfigMailer();
	$e2->key = Mailer::MAIL;
	$e2->text = getMessageString('admin_mailer_mail');
	return array($e0, $e1, $e2);
}


/**
 * Generate the configuration page -> GET /admin/configuration/
 */
function admin_configuration() {
	global $app;

	$mailers = mkMailers();
	$app->render('admin_configuration.html',array(
		'page' => mkPage(getMessageString('admin'), 0, 2),
		'mailers' => mkMailers(),
		'isadmin' => is_admin()));
}

/**
 * Generate the ID templates page -> GET /admin/idtemplates/
 */
function admin_get_idtemplates() {
	global $app;

	$idtemplates = $app->bbs->idTemplates();
	$idtypes = $app->calibre->idTypes();
	$ids2add = array();
	foreach ($idtypes as $idtype) {
		if (empty($idtemplates)) {
			array_push($ids2add, $idtype['type']);
		} else {
			$found = false;
			foreach ($idtemplates as $idtemplate) {
				if ($idtype['type'] === $idtemplate->name) {
					$found = true;
					break;
				}
			}		
			if (!$found)
				array_push($ids2add, $idtype['type']);
		}
	}
	foreach ($ids2add as $id2add) {
		$ni = new IdUrlTemplate();
		$ni->name = $id2add;
		$ni->val = '';
		$ni->label = '';
		array_push($idtemplates, $ni);
	}
	$app->getLog()->debug('admin_get_idtemplates '.var_export($idtemplates, true));
	$app->render('admin_idtemplates.html',array(
		'page' => mkPage(getMessageString('admin_idtemplates'), 0, 2),
		'templates' => $idtemplates,
		'isadmin' => is_admin()));
}

function admin_modify_idtemplate($id) {
	global $app;

	$template_data = $app->request()->put();
	$app->getLog()->debug('admin_modify_idtemplate: '.var_export($template_data, true));
	try {
		$template = $app->bbs->idTemplate($id);
		if (is_null($template))
			$ntemplate = $app->bbs->addIdTemplate($id, $template_data['url'], $template_data['label']);
		else
			$ntemplate = $app->bbs->changeIdTemplate($id, $template_data['url'], $template_data['label']);		
	} catch (Exception $e) {
		$app->getLog()->error('admin_modify_idtemplate: error while adding template'.var_export($template_data, true));
		$app->getLog()->error('admin_modify_idtemplate: exception '.$e->getMessage());
		$ntemplate = null;
	}
	$resp = $app->response();
	if (!is_null($ntemplate)) {
		$resp->status(200);
		$msg = getMessageString('admin_modified');
		$answer = json_encode(array('template' => $ntemplate->getProperties(), 'msg' => $msg));
		$resp->header('Content-type','application/json');
	} else {
		$resp->status(500);
		$resp->header('Content-type','text/plain');
		$answer = getMessageString('admin_modify_error');
	}
	#$app->getLog()->debug('admin_modify_idtemplate 2: '.var_export($ntemplate, true));	
	$resp->header('Content-Length',strlen($answer));
	$resp->body($answer);	
}

function admin_clear_idtemplate($id) {
	global $app;

	$app->getLog()->debug('admin_clear_idtemplate: '.var_export($id, true));	
	$success = $app->bbs->deleteIdTemplate($id);
	$resp = $app->response();
	if ($success) {
		$resp->status(200);
		$msg = getMessageString('admin_modified');
		$answer = json_encode(array('msg' => $msg));
		$resp->header('Content-type','application/json');
	} else {
		$resp->status(404);
		$answer = getMessageString('admin_modify_error');
		$resp->header('Content-type','text/plain');		
	}
	$resp->header('Content-Length',strlen($answer));
	$resp->body($answer);
}

/**
 * Generate the SMTP configuration page -> GET /admin/mail/
 */
function admin_get_smtp_config() {
	global $app, $globalSettings;

	$mail = array('username' => $globalSettings[SMTP_USER],
		'password' => $globalSettings[SMTP_PASSWORD],
		'smtpserver' => $globalSettings[SMTP_SERVER],
		'smtpport' => $globalSettings[SMTP_PORT],
		'smtpenc' => $globalSettings[SMTP_ENCRYPTION]);
	$app->render('admin_mail.html',array(
		'page' => mkPage(getMessageString('admin_mail'), 0, 2),
		'mail' => $mail,
		'encryptions' => mkEncryptions(),
		'isadmin' => is_admin()));
}

function mkEncryptions() {
	$e0 = new Encryption();
	$e0->key = 0;
	$e0->text = getMessageString('admin_smtpenc_none');
	$e1 = new Encryption();
	$e1->key = 1;
	$e1->text = getMessageString('admin_smtpenc_ssl');
	$e2 = new Encryption();
	$e2->key = 2;
	$e2->text = getMessageString('admin_smtpenc_tls');
	return array($e0, $e1, $e2);
}

/**
 * Change the SMTP configuration -> PUT /admin/mail/
 */
function admin_change_smtp_config() {
	global $app;

	$mail_data = $app->request()->put();
	$app->getLog()->debug('admin_change_smtp_configuration: '.var_export($mail_data, true));	
	$mail_config = array(SMTP_USER => $mail_data['username'],
		SMTP_PASSWORD => $mail_data['password'],
		SMTP_SERVER => $mail_data['smtpserver'],
		SMTP_PORT => $mail_data['smtpport'],
		SMTP_ENCRYPTION => $mail_data['smtpenc']);
	$app->bbs->saveConfigs($mail_config);
	$resp = $app->response();
	$app->render('admin_mail.html',array(
		'page' => mkPage(getMessageString('admin_smtp'), 0, 2),
		'mail' => $mail_data,
		'encryptions' => mkEncryptions(),
		'isadmin' => is_admin()));	
}


/**
 * Generate the users overview page -> GET /admin/users/
 */
function admin_get_users() {
	global $app;

	$users = $app->bbs->users();
	$app->render('admin_users.html',array(
		'page' => mkPage(getMessageString('admin_users'), 0, 2),
		'users' => $users,
		'isadmin' => is_admin()));
}


/**
 * Generate the single user page -> GET /admin/users/:id/
 */
function admin_get_user($id) {
	global $app;

	$user = $app->bbs->user($id);
	$languages = $app->calibre->languages();
	foreach ($languages as $language) {
		$language->key = $language->lang_code;
	} 
	$nl = new Language();
	$nl->lang_code = getMessageString('admin_no_selection');
	$nl->key = '';
	array_unshift($languages, $nl);
	$tags = $app->calibre->tags();
	foreach ($tags as $tag) {
		$tag->key = $tag->name;
	}
	$nt = new Tag();
	$nt->name = getMessageString('admin_no_selection');
	$nt->key = '';
	array_unshift($tags, $nt);
	$app->getLog()->debug('admin_get_user: '.var_export($user, true));	
	$app->render('admin_user.html',array(
		'page' => mkPage(getMessageString('admin_users'), 0, 3),
		'user' => $user,
		'languages' => $languages,
		'tags' => $tags,
		'isadmin' => is_admin()));
}

/**
 * Add a user -> POST /admin/users/ (JSON)
 */
function admin_add_user() {
	global $app;

	$user_data = $app->request()->post();
	$app->getLog()->debug('admin_add_user: '.var_export($user_data, true));	
	try {
		$user = $app->bbs->addUser($user_data['username'], $user_data['password']);		
	} catch (Exception $e) {
		$app->getLog()->error('admin_add_user: error for adding user '.var_export($user_data, true));			
		$app->getLog()->error('admin_add_user: exception '.$e->getMessage());			
		$user = null;
	}
	$resp = $app->response();
	if (isset($user) && !is_null($user)) {
		$resp->status(200);
		$msg = getMessageString('admin_modified');
		$answer = json_encode(array('user' => $user->getProperties(), 'msg' => $msg));
		$resp->header('Content-type','application/json');
	} else {
		$resp->status(500);
		$resp->header('Content-type','text/plain');
		$answer = getMessageString('admin_modify_error');
	}
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
		$answer = json_encode(array('msg' => $msg));
		$resp->header('Content-type','application/json');
	} else {
		$resp->status(500);
		$resp->header('Content-type','text/plain');
		$answer = getMessageString('admin_modify_error');
	}
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
	$app->getLog()->debug('admin_modify_user: '.var_export($user, true));	
	$resp = $app->response();
	if (isset($user) && !is_null($user)) {
		$resp->status(200);
		$msg = getMessageString('admin_modified');
		$answer = json_encode(array('user' => $user->getProperties(), 'msg' => $msg));
		$resp->header('Content-type','application/json');
	} else {
		$resp->status(500);
		$resp->header('Content-type','text/plain');
		$answer = getMessageString('admin_modify_error');
	}
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
	# Calibre dir changed, check it for existence, delete thumbnails of old calibre library
	elseif (array_key_exists(CALIBRE_DIR, $req_configs)) {		
		$req_calibre_dir = $req_configs[CALIBRE_DIR];
		if ($req_calibre_dir != $globalSettings[CALIBRE_DIR]) {
			if (!Calibre::checkForCalibre($req_calibre_dir)){
				array_push($errors, 1);
			}elseif($app->bbs->clearThumbnails())
				$app->getLog()->info('admin_change: Lib changed, deleted exisiting thumbnails.');
			else {
				$app->getLog()->info('admin_change: Lib changed, deletion of exisiting thumbnails failed.');
			}
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
		if($app->bbs->clearThumbnails())
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
				$nconfigs[$key] = $value;
				$globalSettings[$key] = $value;
				$app->getLog()->debug('admin_change: '.$key.' changed: '.$value);	
			}
		}
		# Save changes
		if (count($nconfigs) > 0) {
			$app->bbs->saveConfigs($nconfigs);
			$app->getLog()->debug('admin_change: changes saved');					
		}
		$app->getLog()->debug('admin_change: ended');	
		$app->render('admin_configuration.html',array(
			'page' => mkPage(getMessageString('admin'), 0, 2), 
			'messages' => array(getMessageString('changes_saved')),
			'mailers' => mkMailers(),
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
			'page' => mkPage(getMessageString('admin_check_version'), 0, 2), 
			'versionClass' => $versionClass,
			'versionAnswer' => $versionAnswer,
			'isadmin' => true,
			));	
}


/*********************************************************************
 * Metadata functions
 ********************************************************************/

/**
 * Upload an author thumbnail picture -> POST /metadata/authors/:id/thumbnail/
 * Works only with JPG/PNG, max. size 3MB
 */

function edit_author_thm($id) {
	global $app, $globalSettings;

	$allowedExts = array("jpeg", "jpg", "png");
	#$temp = explode(".", $_FILES["file"]["name"]);
	#$extension = end($temp);
	$extension = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
	$app->getLog()->debug('edit_author_thm: '.$_FILES["file"]["name"]);
	if ((($_FILES["file"]["type"] == "image/jpeg")
	|| ($_FILES["file"]["type"] == "image/jpg")
	|| ($_FILES["file"]["type"] == "image/pjpeg")
	|| ($_FILES["file"]["type"] == "image/x-png")
	|| ($_FILES["file"]["type"] == "image/png"))
	&& ($_FILES["file"]["size"] < 3145728)
	&& in_array($extension, $allowedExts)) {
		$app->getLog()->debug('edit_author_thm: filetype '.$_FILES["file"]["type"].', size '.$_FILES["file"]["size"]);
		if ($_FILES["file"]["error"] > 0) {
			$app->getLog()->debug('edit_author_thm: upload error '.$_FILES["file"]["error"]);
			$app->flash('error', getMessageString('author_thumbnail_upload_error1').': '.$_FILES["file"]["error"]);
			$rot = $app->request()->getRootUri();
			$app->redirect($rot.'/authors/'.$id.'/0/');
		} else {
			$app->getLog()->debug('edit_author_thm: upload ok, converting');
			$author = $app->calibre->findOne('Author', 'select * from authors where id='.$id);
			$created = $app->bbs->editAuthorThumbnail($id, $author->name, $globalSettings[THUMB_GEN_CLIPPED], $_FILES["file"]["tmp_name"], $_FILES["file"]["type"]);
			$app->getLog()->debug('edit_author_thm: converted, redirecting');
			$rot = $app->request()->getRootUri();
			$app->redirect($rot.'/authors/'.$id.'/0/');			
		}
	} else {
		$app->flash('error', getMessageString('author_thumbnail_upload_error2'));
		$rot = $app->request()->getRootUri();
		$app->redirect($rot.'/authors/'.$id.'/0/');
	}	
}

/**
 * Delete the author's thumbnail -> DELETE /metadata/authors/:id/thumbnail/ JSON
 */
function del_author_thm($id) {
	global $app;

	$app->getLog()->debug('del_author_thm: '.$id);
	$del = $app->bbs->deleteAuthorThumbnail($id);
	$resp = $app->response();
	if ($del) {
		$resp->status(200);
		$msg = getMessageString('admin_modified');
		$answer = json_encode(array('msg' => $msg));
		$resp->header('Content-type','application/json');
	} else {
		$resp->status(500);
		$resp->header('Content-type','text/plain');
		$answer = getMessageString('admin_modify_error');
	}
	$resp->header('Content-Length',strlen($answer));
	$resp->body($answer);	
}

/**
 * Edit the notes about the author -> POST /metadata/authors/:id/notes/ JSON
 */
function edit_author_notes($id) {
	global $app;

	$app->getLog()->debug('edit_author_notes: '.$id);
	$note_data = $app->request()->post();
	$app->getLog()->debug('edit_author_notes: note '.var_export($note_data, true));	
	try {
		$markdownParser = new MarkdownExtraParser();
		$html = $markdownParser->transformMarkdown($note_data['ntext']);
		$author = $app->calibre->author($id);
		$note = $app->bbs->editAuthorNote($id, $author->name, $note_data['mime'], $note_data['ntext']);		
	} catch (Exception $e) {
		$note = null;		
	}
	$resp = $app->response();
	if (!is_null($note)) {
		$resp->status(200);
		$msg = getMessageString('admin_modified');
		$note2 = $note->getProperties();
		$note2['html'] = $html;
		$answer = json_encode(array('note' => $note2, 'msg' => $msg));
		$resp->header('Content-type','application/json');
	} else {
		$resp->status(500);
		$resp->header('Content-type','text/plain');
		$answer = getMessageString('admin_modify_error');
	}
	$resp->header('Content-Length',strlen($answer));
	$resp->body($answer);
}

/**
 * Delete notes about the author -> DELETE /metadata/authors/:id/notes/ JSON
 */
function del_author_notes($id) {
	global $app;

	$app->getLog()->debug('del_author_notes: '.$id);
	$del = $app->bbs->deleteAuthorNote($id);
	$resp = $app->response();
	if ($del) {
		$resp->status(200);
		$msg = getMessageString('admin_modified');
		$answer = json_encode(array('msg' => $msg));
		$resp->header('Content-type','application/json');
	} else {
		$resp->status(500);
		$resp->header('Content-type','text/plain');
		$answer = getMessageString('admin_modify_error');
	}
	$resp->header('Content-Length',strlen($answer));
	$resp->body($answer);		
}

/**
 * Add a new author link -> POST /metadata/authors/:id/links JSON
 */
function new_author_link($id) {
	global $app;

	$link_data = $app->request()->post();
	$app->getLog()->debug('new_author_link: '.var_export($link_data, true));	
	$author = $app->calibre->author($id);
	$link = null;
	if (!is_null($author)) {
		$link = $app->bbs->addAuthorLink($id, $author->name, $link_data['label'], $link_data['url']);
	}
	$resp = $app->response();
	if (!is_null($link)) {
		$resp->status(200);
		$msg = getMessageString('admin_modified');
		$answer = json_encode(array('link' => $link->getProperties(), 'msg' => $msg));
		$resp->header('Content-type','application/json');
	} else {
		$resp->status(500);
		$resp->header('Content-type','text/plain');
		$answer = getMessageString('admin_modify_error');
	}
	$resp->header('Content-Length',strlen($answer));
	$resp->body($answer);
}

/**
 * Delete an author link -> DELETE /metadata/authors/:id/links/:link/ JSON
 */
function del_author_link($id, $link) {
	global $app;

	$app->getLog()->debug('del_author_link: author '.$id.', link '.$link);	
	$ret = $app->bbs->deleteAuthorLink($id, $link);
	$resp = $app->response();
	if ($ret) {
		$resp->status(200);
		$msg = getMessageString('admin_modified');
		$answer = json_encode(array('msg' => $msg));
		$resp->header('Content-type','application/json');
	} else {
		$resp->status(500);
		$resp->header('Content-type','text/plain');
		$answer = getMessageString('admin_modify_error');
	}
	$resp->header('Content-Length',strlen($answer));
	$resp->body($answer);
}

/*********************************************************************
 * HTML presentation functions
 ********************************************************************/

/**
 * Generate the main page with the 30 mos recent titles
 */
function main() {
	global $app, $globalSettings;

	$filter = getFilter();
	$books1 = $app->calibre->last30Books($globalSettings['lang'], $globalSettings[PAGE_SIZE], $filter);
	$books = array_map('checkThumbnail', $books1);	
	$app->render('index_last30.html',array(
		'page' => mkPage(getMessageString('dl30'),1, 1), 
		'books' => $books));	
}


# Make a search over all categories. Returns only the first PAGES_SIZE items per category.
# If there are more entries per category, there will be a link to the full results.
function globalSearch() {
	global $app, $globalSettings;

	$filter = getFilter();
	$search = $app->request()->get('search');
	$tlb = $app->calibre->titlesSlice($globalSettings['lang'], 0, $globalSettings[PAGE_SIZE], $filter, trim($search));
	$tlb_books = array_map('checkThumbnail', $tlb['entries']);	
	$tla = $app->calibre->authorsSlice(0, $globalSettings[PAGE_SIZE], trim($search));
	$tla_books = array_map('checkThumbnail', $tla['entries']);	
	$tlt = $app->calibre->tagsSlice(0, $globalSettings[PAGE_SIZE],  trim($search));
	$tlt_books = array_map('checkThumbnail', $tlt['entries']);	
	$tls = $app->calibre->seriesSlice(0, $globalSettings[PAGE_SIZE], trim($search));
	$tls_books = array_map('checkThumbnail', $tls['entries']);	
	$app->render('global_search.html',array(
		'page' => mkPage(getMessageString('pagination_search'),0), 
		'books' => $tlb_books,
		'books_total' => $tlb['total'] == -1 ? 0 : $tlb['total'],
		'more_books' => ($tlb['total'] > $globalSettings[PAGE_SIZE]),
		'authors' => $tla_books,
		'authors_total' => $tla['total'] == -1 ? 0 : $tla['total'],
		'more_authors' => ($tla['total'] > $globalSettings[PAGE_SIZE]),
		'tags' => $tlt_books,
		'tags_total' => $tlt['total'] == -1 ? 0 : $tlt['total'],
		'more_tags' => ($tlt['total'] > $globalSettings[PAGE_SIZE]),
		'series' => $tls_books,
		'series_total' => $tls['total'] == -1 ? 0 : $tls['total'],
		'more_series' => ($tls['total'] > $globalSettings[PAGE_SIZE]),
		'search' => $search));
}


# A list of titles at $index -> /titlesList/:index
function titlesSlice($index=0) {
	global $app, $globalSettings;

	$filter = getFilter();
	$search = $app->request()->get('search');
	if (isset($search)) {
		$tl = $app->calibre->titlesSlice($globalSettings['lang'], $index,$globalSettings[PAGE_SIZE], $filter, trim($search));
	} else {
		$tl = $app->calibre->titlesSlice($globalSettings['lang'], $index,$globalSettings[PAGE_SIZE], $filter);
	}
	$books = array_map('checkThumbnail', $tl['entries']);	
	$app->render('titles.html',array(
		'page' => mkPage(getMessageString('titles'),2, 1), 
		'url' => 'titleslist',
		'books' => $books,
		'curpage' => $tl['page'],
		'pages' => $tl['pages'],
		'search' => $search));
}

# Show a single title > /titles/:id. The ID ist the Calibre ID
function title($id) {
	global $app, $globalSettings;
	
	$details = $app->calibre->titleDetails($globalSettings['lang'], $id);	
	if (is_null($details)) {
		$app->getLog()->warn("title: book not found: ".$id);
		$app->notFound();
		return;
	}	
	// for people trying to circumvent filtering by direct access
	if (title_forbidden($details)) {
		$app->getLog()->warn("title: requested book not allowed for user: ".$id);
		$app->notFound();
		return;
	}	
	// Show ID links only if there are templates and ID data
	$idtemplates = $app->bbs->idTemplates();
	$id_tmpls = array();
	if (count($idtemplates) > 0 && count($details['ids']) > 0) {
		$show_idlinks = true;
		foreach ($idtemplates as $idtemplate) {
			$id_tmpls[$idtemplate->name] = array($idtemplate->val, $idtemplate->label);
		}
	} else 
		$show_idlinks = false;
	$kindle_format = ($globalSettings[KINDLE] == 1) ? $app->calibre->titleGetKindleFormat($id): NULL;
	$app->getLog()->debug('titleDetails custom columns: '.count($details['custom']));
	$app->render('title_detail.html',
		array('page' => mkPage(getMessageString('book_details'), 2, 2), 
			'book' => $details['book'], 
			'authors' => $details['authors'],
			'series' => $details['series'],
			'tags' => $details['tags'], 
			'formats'=>$details['formats'], 
			'comment' => $details['comment'],
			'language' => $details['language'],
			'ccs' => (count($details['custom']) > 0 ? $details['custom'] : null),
			'show_idlinks' => $show_idlinks,
			'ids' => $details['ids'],
			'id_templates' => $id_tmpls,
			'kindle_format' => $kindle_format,
			'kindle_from_email' => $globalSettings[KINDLE_FROM_EMAIL],
			'protect_dl' => false)
	);
}


# Return the cover for the book with ID. Calibre generates only JPEGs, so we always return a JPEG.
# If there is no cover, return 404.
# Route: /titles/:id/cover
function cover($id) {
	global $app;

	$has_cover = false;
	$rot = $app->request()->getRootUri();
	$book = $app->calibre->title($id);
	if (is_null($book)) {
		$app->getLog()->debug("cover: book not found: "+$id);
		$app->response()->status(404);
		return;
	}
	
	if ($book->has_cover) {		
		$cover = $app->calibre->titleCover($id);
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
	global $app, $globalSettings;

	$app->getLog()->debug('thumbnail: '.$id);
	$has_cover = false;
	$rot = $app->request()->getRootUri();
	$book = $app->calibre->title($id);
	if (is_null($book)) {
		$app->getLog()->error("thumbnail: book not found: "+$id);
		$app->response()->status(404);
		return;
	}
	
	if ($book->has_cover) {		
		$cover = $app->calibre->titleCover($id);
		$thumb = $app->bbs->titleThumbnail($id, $cover, $globalSettings[THUMB_GEN_CLIPPED]);
		$app->getLog()->debug('thumbnail: thumb found '.$thumb);
		$has_cover = true;
	}
	if ($has_cover) {
		$app->response()->status(200);
		$app->response()->header('Content-type','image/png;base64');
		$app->response()->header('Content-Length',filesize($thumb));
		readfile($thumb);		
	} else {
		$app->response()->status(404);
	}
}


# Return the selected file for the book with ID. 
# Route: /titles/:id/file/:file
function book($id, $file) {
	global $app, $globalSettings;

	$details = $app->calibre->titleDetails($globalSettings['lang'], $id);
	if (is_null($details)) {
		$app->getLog()->warn("book: no book found for ".$id);
		$app->notFound();
	}	
	// for people trying to circumvent filtering by direct access
	if (title_forbidden($details)) {
		$app->getLog()->warn("book: requested book not allowed for user: ".$id);
		$app->notFound();
		return;
	}	

	$real_bookpath = $app->calibre->titleFile($id, $file);
	$contentType = Utilities::titleMimeType($real_bookpath);
	$app->getLog()->info("book download for ".$real_bookpath.
	" with metadata update = ".$globalSettings[METADATA_UPDATE]);
	if ($contentType == Utilities::MIME_EPUB && $globalSettings[METADATA_UPDATE]) {
		if ($details['book']->has_cover == 1)
			$cover = $app->calibre->titleCover($id);
		else
			$cover = null;
		// If an EPUB update the metadata
		$mdep = new MetadataEpub($real_bookpath);
		$mdep->updateMetadata($details, $cover);		
		$bookpath = $mdep->getUpdatedFile();
		$app->getLog()->debug("book(e): file ".$bookpath);
		$app->getLog()->debug("book(e): type ".$contentType);
		$booksize = filesize($bookpath);
		$app->getLog()->debug("book(e): size ".$booksize);
		if ($booksize > 0)
			header("Content-Length: ".$booksize);
		header("Content-Type: ".$contentType);
		header("Content-Disposition: ".$file);
		readfile_chunked($bookpath);
	} else {
		// Else send the file as is
		$bookpath = $real_bookpath;
		$app->getLog()->debug("book: file ".$bookpath);
		$app->getLog()->debug("book: type ".$contentType);
		$booksize = filesize($bookpath);
		$app->getLog()->debug("book: size ".$booksize);
		header("Content-Length: ".$booksize);
		header("Content-Type: ".$contentType);
		header("Content-Disposition: ".$file);
		readfile_chunked($bookpath);
	}
}


# Send the selected file to a Kindle e-mail address
# Route: /titles/:id/kindle/:file
function kindle($id, $file) {
	global $app, $globalSettings;
	$book = $app->calibre->title($id);

	if (is_null($book)) {
		$app->getLog()->debug("kindle: book not found: ".$id);
		$app->response()->status(404);
		return;
	}	

	$details = $app->calibre->titleDetails($globalSettings['lang'], $id);
	$filename = "";
	if($details['series']!=null)
	{
		$filename .= $details['series'][0]->name;
		$filename .= "[" . $details['book']->series_index ."] ";
		
	}
	$filename .= $details['book']->title;
	$filename .= " - ";
	foreach ($details['authors'] as $author) {
			$filename.=$author->name;
	}
	$filename.=".mobi";
	# Validate request e-mail format
	$to_email = $app->request()->post('email');
	if (!isEMailValid($to_email)) {
		$app->getLog()->debug("kindle: invalid email, ".$to_email);	
		$app->response()->status(400);
		return;
	} else {
		$app->deleteCookie(KINDLE_COOKIE);
		$bookpath = $app->calibre->titleFile($id, $file);
		$app->getLog()->debug("kindle: requested file ".$bookpath);
		if ($globalSettings[MAILER] == Mailer::SMTP)  {
			$mail = array('username' => $globalSettings[SMTP_USER],
				'password' => $globalSettings[SMTP_PASSWORD],
				'smtp-server' => $globalSettings[SMTP_SERVER],
				'smtp-port' => $globalSettings[SMTP_PORT]);
			if ($globalSettings[SMTP_ENCRYPTION] == 1)
				$mail['smtp-encryption'] = Mailer::SSL;
			elseif ($globalSettings[SMTP_ENCRYPTION] == 2) {
				$mail['smtp-encryption'] = Mailer::TLS;
			}
			$app->getLog()->debug('kindle mail config: '.var_export($mail, true));
			$mailer = new Mailer(Mailer::SMTP, $mail);
		} elseif ($globalSettings[MAILER] == Mailer::SENDMAIL) {
			$mailer = new Mailer(Mailer::SENDMAIL);
		} else {
			$mailer = new Mailer(Mailer::MAIL);
		}
		$send_success = 0;
		try {
			$message = $mailer->createBookMessage($bookpath, $globalSettings[DISPLAY_APP_NAME], $to_email, $globalSettings[KINDLE_FROM_EMAIL],$filename);
			$send_success = $mailer->sendMessage($message);
			if ($send_success == 0)
				$app->getLog()->warn('kindle: book delivery to '.$to_email.' failed, dump: '.$mailer->getDump());
			else
				$app->getLog()->debug('kindle: book delivered to '.$to_email.', result '.$send_success);
		# if there was an exception, log it and return gracefully
		} catch(Exception $e) {
			$app->getLog()->warn('kindle: Email exception '.$e->getMessage());
			$app->getLog()->warn('kindle: Mail dump '.$mailer->getDump());
		}
		# Store e-mail address in cookie so user needs to enter it only once
		$app->setCookie(KINDLE_COOKIE, $to_email);
		if ($send_success > 0)
			echo getMessageString('send_success');
		else
			$app->response()->status(503);
	}
}

# A list of authors at $index -> /authorslist/:index
function authorsSlice($index=0) {
	global $app, $globalSettings;

	$search = $app->request()->get('search');
	if (isset($search))
		$tl = $app->calibre->authorsSlice($index,$globalSettings[PAGE_SIZE], trim($search));	
	else
		$tl = $app->calibre->authorsSlice($index,$globalSettings[PAGE_SIZE]);

	foreach ($tl['entries'] as $author) {
		$author->thumbnail = $app->bbs->getAuthorThumbnail($author->id);
		if ($author->thumbnail)
			$app->getLog()->debug('authorsSlice thumbnail '.var_export($author->thumbnail->url,true));
	}
	$app->render('authors.html',array(
		'page' => mkPage(getMessageString('authors'),3, 1), 
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
	global $app, $globalSettings;

	$details = $app->calibre->authorDetails($id);
	if (is_null($details)) {
		$app->getLog()->debug("no author");
		$app->notFound();		
	}
	$app->render('author_detail.html',array(
		'page' => mkPage(getMessageString('author_details'), 3, 2), 
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
	global $app, $globalSettings;
  
	$filter = getFilter();
	$tl = $app->calibre->authorDetailsSlice($globalSettings['lang'], $id, $index, $globalSettings[PAGE_SIZE], $filter);
	if (is_null($tl)) {
		$app->getLog()->debug('no author '.$id);
		$app->notFound();
	}
	$books = array_map('checkThumbnail', $tl['entries']);	
	$author = $tl['author'];
	$author->thumbnail = $app->bbs->getAuthorThumbnail($id);
	$note = $app->bbs->authorNote($id);
	if (!is_null($note))
		$author->notes_source = $note->ntext;		
	else
		$author->notes_source = null;
	if (!empty($author->notes_source)) {
		$markdownParser = new MarkdownExtraParser();
		$author->notes = $markdownParser->transformMarkdown($author->notes_source);		
	} else {
		$author->notes = null;
	}

	$author->links = $app->bbs->authorLinks($id);
	$app->render('author_detail.html',array(
		'page' => mkPage(getMessageString('author_details'), 3, 2),
		'url' => 'authors/'.$id,	
		'author' => $tl['author'],
		'books' => $books,
		'curpage' => $tl['page'],
		'pages' =>  $tl['pages'],
		'isadmin' => is_admin()));
}


/**
 * Notes for a single author -> /authors/:id/notes/
 * 
 * @param  integer $id    author id
 * @return HTML page 
 */
function authorNotes($id) {
	global $app, $globalSettings;
  
	$author = $app->calibre->author($id);
	if (is_null($author)) {
		$app->getLog()->debug('no author found: '.$id);
		$app->notFound();
	}
	$note = $app->bbs->authorNote($id);
	if (!is_null($note))
		$author->notes_source = $note->ntext;		
	else
		$author->notes_source = null;
	if (!empty($author->notes_source)) {
		$markdownParser = new MarkdownExtraParser();
		$author->notes = $markdownParser->transformMarkdown($author->notes_source);		
	} else {
		$author->notes = null;
	}
	$app->render('author_notes.html',array(
		'page' => mkPage(getMessageString('author_notes'), 3, 2),
		'url' => 'authors/'.$id,	
		'author' => $author,
		'isadmin' => is_admin()));
}


/**
 * Return a HTML page of series at page $index. 
 * @param  integer $index=0 page index into series list
 */
function seriesSlice($index=0) {
	global $app, $globalSettings;

	$search = $app->request()->get('search');
	if (isset($search)) {
		$app->getLog()->debug('seriesSlice: search '.$search);			
		$tl = $app->calibre->seriesSlice($index, $globalSettings[PAGE_SIZE], trim($search));	
	} else
		$tl = $app->calibre->seriesSlice($index, $globalSettings[PAGE_SIZE]);
	$app->render('series.html',array(
		'page' => mkPage(getMessageString('series'),5, 1), 
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
	global $app, $globalSettings;

	$details = $app->calibre->seriesDetails($id);
	if (is_null($details)) {
		$app->getLog()->debug('no series '.$id);
		$app->notFound();		
	}
	$app->render('series_detail.html',array(
		'page' => mkPage(getMessageString('series_details'), 5, 3), 
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
	global $app, $globalSettings;

	$filter = getFilter();
	$tl = $app->calibre->seriesDetailsSlice($globalSettings['lang'], $id, $index, $globalSettings[PAGE_SIZE], $filter);
	if (is_null($tl)) {
		$app->getLog()->debug('no series '.$id);
		$app->notFound();		
	}
	$books = array_map('checkThumbnail', $tl['entries']);	
	$app->render('series_detail.html',array(
		'page' => mkPage(getMessageString('series_details'), 5, 2),
		'url' => 'series/'.$id, 
		'series' => $tl['series'], 
		'books' => $books,
		'curpage' => $tl['page'],
		'pages' => $tl['pages']));   
}


# A list of tags at $index -> /tagslist/:index
function tagsSlice($index=0) {
	global $app, $globalSettings;

	$search = $app->request()->get('search');
	if (isset($search))
		$tl = $app->calibre->tagsSlice($index,$globalSettings[PAGE_SIZE],trim($search));
	else
		$tl = $app->calibre->tagsSlice($index,$globalSettings[PAGE_SIZE]);
	$app->render('tags.html',array(
		'page' => mkPage(getMessageString('tags'),4, 1), 
		'url' => 'tagslist',
		'tags' => $tl['entries'],
		'curpage' => $tl['page'],
		'pages' => $tl['pages'],
		'search' => $search));
}

# Details for a single tag -> /tags/:id/:page
# @deprecated since 0.9.3
function tag($id) {
	global $app, $globalSettings;

	$details = $app->calibre->tagDetails($id);
	if (is_null($details)) {
		$app->getLog()->debug("no tag");
		$app->notFound();		
	}
	$app->render('tag_detail.html',array(
		'page' => mkPage(getMessageString('tag_details'), 4, 3), 
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
	global $app, $globalSettings;

	$filter = getFilter();
	$tl = $app->calibre->tagDetailsSlice($globalSettings['lang'], $id, $index, $globalSettings[PAGE_SIZE], $filter);
	if (is_null($tl)) {
		$app->getLog()->debug('no tag '.$id);
		$app->notFound();		
	}
	$books = array_map('checkThumbnail', $tl['entries']);	
	$app->render('tag_detail.html',array(
		'page' => mkPage(getMessageString('tag_details'), 4, 2),
		'url' => 'tags/'.$id, 
		'tag' => $tl['tag'], 
		'books' => $books,
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
	global $app, $globalSettings;

	$filter = getFilter();
	$just_books = $app->calibre->last30Books($globalSettings['lang'], $globalSettings[PAGE_SIZE], $filter);
	$books1 = array();
	foreach ($just_books as $book) {
		$record = $app->calibre->titleDetailsOpds($book);
		if (!empty($record['formats']))
			array_push($books1, $record);
	}
	$books = array_map('checkThumbnailOpds', $books1);	
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

	$filter = getFilter();
	$search = $app->request()->get('search');
	if (isset($search))
		$tl = $app->calibre->titlesSlice($globalSettings['lang'], $index,$globalSettings[PAGE_SIZE], $filter, $search);
	else
		$tl = $app->calibre->titlesSlice($globalSettings['lang'], $index,$globalSettings[PAGE_SIZE], $filter);
	$books1 = $app->calibre->titleDetailsFilteredOpds($tl['entries']);
	$books = array_map('checkThumbnailOpds', $books1);	
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

	$initials = $app->calibre->authorsInitials();
	$gen = mkOpdsGenerator($app);	
	$cat = $gen->authorsRootCatalog(NULL, $initials);
	mkOpdsResponse($app, $cat, OpdsGenerator::OPDS_MIME_NAV);
}

/**
 * Return a page with author names for a initial
 */
function opdsByAuthorNamesForInitial($initial) {
	global $app;

	$authors = $app->calibre->authorsNamesForInitial($initial);
	$gen = mkOpdsGenerator($app);	
	$cat = $gen->authorsNamesForInitialCatalog(NULL, $authors, $initial);
	mkOpdsResponse($app, $cat, OpdsGenerator::OPDS_MIME_NAV);
}

/**
 * Return a feed with partial acquisition entries for the author's books
 * @param  string 	initial initial character
 * @param  int 		id      author id
 * @param  int 		page    page number
 */
function opdsByAuthor($initial, $id, $page) {
	global $app, $globalSettings;

	$filter = getFilter();
	$tl = $app->calibre->authorDetailsSlice($globalSettings['lang'], $id, $page, $globalSettings[PAGE_SIZE], $filter);
	$app->getLog()->debug('opdsByAuthor 1 '.var_export($tl, true));
	$books1 = $app->calibre->titleDetailsFilteredOpds($tl['entries']);
	$books = array_map('checkThumbnailOpds', $books1);	
	$app->getLog()->debug('opdsByAuthor 2 '.var_export($books, true));
	$gen = mkOpdsGenerator($app);	
	$cat = $gen->booksForAuthorCatalog(NULL, $books, $initial, $tl['author'], false, 
		$tl['page'], getNextSearchPage($tl), getLastSearchPage($tl));
	mkOpdsResponse($app, $cat, OpdsGenerator::OPDS_MIME_ACQ);
}

/**
 * Return a page with tag initials
 */
function opdsByTagInitial() {
	global $app;

	$initials = $app->calibre->tagsInitials();
	$gen = mkOpdsGenerator($app);	
	$cat = $gen->tagsRootCatalog(NULL, $initials);
	mkOpdsResponse($app, $cat, OpdsGenerator::OPDS_MIME_NAV);
}

/**
 * Return a page with author names for a initial
 */
function opdsByTagNamesForInitial($initial) {
	global $app;

	$tags = $app->calibre->tagsNamesForInitial($initial);
	$gen = mkOpdsGenerator($app);	
	$cat = $gen->tagsNamesForInitialCatalog(NULL, $tags, $initial);
	mkOpdsResponse($app, $cat, OpdsGenerator::OPDS_MIME_NAV);
}

/**
 * Return a feed with partial acquisition entries for the tags's books
 * @param  string 	initial initial character
 * @param  int 		id      tag id
 * @param  int 		page 	page index
 */
function opdsByTag($initial, $id, $page) {
	global $app, $globalSettings;

	$filter = getFilter();
	$tl = $app->calibre->tagDetailsSlice($globalSettings['lang'], $id, $page, $globalSettings[PAGE_SIZE], $filter);
	$books1 = $app->calibre->titleDetailsFilteredOpds($tl['entries']);
	$books = array_map('checkThumbnailOpds', $books1);	
	$gen = mkOpdsGenerator($app);	
	$cat = $gen->booksForTagCatalog(NULL, $books, $initial, $tl['tag'], false,
		$tl['page'], getNextSearchPage($tl), getLastSearchPage($tl));
	mkOpdsResponse($app, $cat, OpdsGenerator::OPDS_MIME_ACQ);
}

/**
 * Return a page with series initials
 */
function opdsBySeriesInitial() {
	global $app;

	$initials = $app->calibre->seriesInitials();
	$gen = mkOpdsGenerator($app);	
	$cat = $gen->seriesRootCatalog(NULL, $initials);
	mkOpdsResponse($app, $cat, OpdsGenerator::OPDS_MIME_NAV);
}

/**
 * Return a page with author names for a initial
 */
function opdsBySeriesNamesForInitial($initial) {
	global $app;

	$tags = $app->calibre->seriesNamesForInitial($initial);
	$gen = mkOpdsGenerator($app);	
	$cat = $gen->seriesNamesForInitialCatalog(NULL, $tags, $initial);
	mkOpdsResponse($app, $cat, OpdsGenerator::OPDS_MIME_NAV);
}

/**
 * Return a feed with partial acquisition entries for the series' books
 * @param  string 	initial initial character
 * @param  int 		id     	tag id
 * @param  int 		page 	page index
 */
function opdsBySeries($initial, $id, $page) {
	global $app, $globalSettings;

	$filter = getFilter();
	$tl = $app->calibre->seriesDetailsSlice($globalSettings['lang'], $id, $page, $globalSettings[PAGE_SIZE], $filter);
	$books1 = $app->calibre->titleDetailsFilteredOpds($tl['entries']);
	$books = array_map('checkThumbnailOpds', $books1);	
	$gen = mkOpdsGenerator($app);	
	$cat = $gen->booksForSeriesCatalog(NULL, $books, $initial, $tl['series'], false,
		$tl['page'], getNextSearchPage($tl), getLastSearchPage($tl));
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
	$filter = getFilter();
	$tl = $app->calibre->titlesSlice($globalSettings['lang'], $index, $globalSettings[PAGE_SIZE], $filter, $search);	
	$books1 = $app->calibre->titleDetailsFilteredOpds($tl['entries']);
	$books = array_map('checkThumbnailOpds', $books1);	
	$gen = mkOpdsGenerator($app);
	$cat = $gen->searchCatalog(NULL, $books, false, 
		$tl['page'], getNextSearchPage($tl), getLastSearchPage($tl), $search, 
		$tl['total'], $globalSettings[PAGE_SIZE]);
	mkOpdsResponse($app, $cat, OpdsGenerator::OPDS_MIME_ACQ);	
}

/*********************************************************************
 * Utility and helper functions, private
 ********************************************************************/

function checkThumbnail($book) {
	global $app;
	$book->thumbnail = $app->bbs->isTitleThumbnailAvailable($book->id); 
	return $book;
}

function checkThumbnailOpds($record) {
	global $app;
	$record['book']->thumbnail = $app->bbs->isTitleThumbnailAvailable($record['book']->id); 
	return $record;
}


function getFilter() {
	global $app;

	$user = $app->strong->getUser();
	$app->getLog()->debug('getFilter: '.var_export($user,true));	
	$lang = null;
	$tag = null;
	if (!empty($user['languages']))
		$lang = $app->calibre->getLanguageId($user['languages']);
	if (!empty($user['tags']))
		$tag = $app->calibre->getTagId($user['tags']);
	$app->getLog()->debug('getFilter: Using language '.$lang.', tag '.$tag);
	return new CalibreFilter($lang, $tag);
}

# Initialize the OPDS generator
function mkOpdsGenerator($app) {
	global $appversion, $globalSettings;
	$root = rtrim($app->request()->getUrl().$app->request()->getRootUri(), "/");
	$gen = new OpdsGenerator($root, $appversion, 
		$app->calibre->calibre_dir,
		date(DATE_ATOM, $app->calibre->calibre_last_modified),
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
function mkPage($subtitle='', $menu=0, $level=0) {
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
		'level' => $level,
		'auth' => $auth,
		'admin' => is_admin());
	return $page;
}

/**
 * Checks if a title is available to the current users
 * @param details 	output of BicBucStriim::title_details()
 * @return  		true if the title is not availble for the user, else false
 */
function title_forbidden($book_details) {
	global $app;

	$user = $app->strong->getUser();
	if (empty($user['languages']) && empty($user['tags'])) {
		return false;
	}
	else {
		if (!empty($user['languages'])) {
			$lang_found = false;
			foreach ($book_details['langcodes'] as $langcode) {
				if ($langcode === $user['languages']) {
					$lang_found = true;					
					break;
				}
			}			
			if (!$lang_found) {
				return true;
			}
		}
		if (!empty($user['tags'])) {
			$tag_found = false;
			foreach ($book_details['tags'] as $tag) {
				if ($tag->name === $user['tags']) {
					$tag_found = true;
					break;
				}
			}			
			if ($tag_found) {
				return true;
			}
		}
		return false;
	}
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
