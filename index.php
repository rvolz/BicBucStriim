<?php
// Copyight 2012 Rainer Volz
// Licensed under MIT License, see README.MD/License

require_once 'lib/Slim/Slim.php';
require_once 'lib/Slim/Views/TwigView.php';
TwigView::$twigDirectory = dirname(__FILE__) . '/lib/Twig';
TwigView::$twigExtensions = array(
    'Twig_Extensions_Slim'
);

require_once 'lib/BicBucStriim/bicbucstriim.php';
require_once 'lib/BicBucStriim/langs.php';

# Allowed languages, i.e. languages with translations
$allowedLangs = array('de','en','fr');
# Fallback language if the browser prefers other than the allowed languages
$fallbackLang = 'en';
# Application Name
$appname = 'BicBucStriim';
# App version
$appversion = '0.9.0';
# Cookie name for global download protection
define('GLOBAL_DL_COOKIE', 'glob_dl_access');
# Cookie name for admin access
define('ADMIN_COOKIE', 'admin_access');
# Admin password
define('ADMIN_PW', 'admin_pw');
# Calibre library path
define('CALIBRE_DIR', 'calibre_dir');
# Global download choice
define('GLOB_DL_CHOICE', 'glob_dl_choice');
# Global download password
define('GLOB_DL_PASSWORD', 'glob_dl_password');
# BicBucStriim DB version
define('DB_VERSION', 'db_version');

# Init app and routes
$app = new Slim(array(
	'debug' => true,
	'log.enabled' => true, 
	#'log.writer' => new Slim_LogFileWriter(fopen('./data/bbs.log','a')),
	'log.level' => 4,
	'view' => new TwigView(),
	));

# Init app globals
$globalSettings = array();
$globalSettings['appname'] = $appname;
$globalSettings['version'] = $appversion;
$globalSettings['sep'] = ' :: ';
$globalSettings['lang'] = getUserLang($allowedLangs, $fallbackLang);
if ($globalSettings['lang'] == 'de')
	$globalSettings['langa'] = $langde;
elseif ($globalSettings['lang'] == 'fr')
	$globalSettings['langa'] = $langfr;
else
	$globalSettings['langa'] = $langen;

# Add globals from DB
$bbs = new BicBucStriim();
if ($bbs->dbOk()) {
	$app->getLog()->debug("config db found");
	$we_have_config = true;
	$css = $bbs->configs();
	foreach ($css as $config) {
		switch ($config->name) {
			case ADMIN_PW:
				$globalSettings[ADMIN_PW] = $config->val;
				break;			
			case CALIBRE_DIR:
				$globalSettings[CALIBRE_DIR] = $config->val;
				break;
			case DB_VERSION:
				$globalSettings[DB_VERSION] = $config->val;
				break;
			case GLOB_DL_PASSWORD:
				$globalSettings[GLOB_DL_PASSWORD] = $config->val;
				break;
			case GLOB_DL_CHOICE:
				$globalSettings[GLOB_DL_CHOICE] = $config->val;
				break;
			default:
				$app->getLog()->warn(join('',array('Unknown configuration, name: ',
					$config->name,', value: ',$config->val)));	
		}
	}
	$app->getLog()->debug("config loaded");
} else {
	$app->getLog()->debug("no config db found");
	$we_have_config = false;
}



# Init routes
$app->notFound('myNotFound');
$app->get('/', 'check_config', 'main');
$app->get('/admin/', 'admin');
$app->post('/admin/', 'admin_change_json');
$app->get('/admin/access/', 'admin_is_protected');
$app->post('/admin/access/check/', 'admin_checkaccess');
$app->get('/admin/error/:id', 'admin_error');
$app->get('/titles/', 'check_config', 'titles');
$app->get('/titles/:id/', 'check_config','title');
$app->get('/titles/:id/showaccess/', 'check_config', 'showaccess');
$app->post('/titles/:id/checkaccess/', 'check_config', 'checkaccess');
$app->get('/titles/:id/cover/', 'check_config', 'cover');
$app->get('/titles/:id/file/:file', 'check_config', 'book');
$app->get('/titles/:id/thumbnail/', 'check_config', 'thumbnail');
$app->get('/authors/', 'check_config', 'authors');
$app->get('/authors/:id/', 'check_config', 'author');
$app->get('/tags/', 'check_config', 'tags');
$app->get('/tags/:id/', 'check_config', 'tag');

$app->run();


# Check if the configuration is valid:
# - If there is no bbs db --> show error
function check_config() {
	global $we_have_config, $bbs, $app, $globalSettings;

	$app->getLog()->debug("check_config start");
	# No config --> error
	if (!$we_have_config) {
		$app->getLog()->error('No configuration found');	
		$app->render('error.html', array(
			'page' => mkPage($globalSettings['langa']['error']), 
			'title' => $globalSettings['langa']['error'], 
			'error' => $globalSettings['langa']['no_config']));
	}

	# 'After installation' scenario: here is a config DB but no valid connection to Calibre
	if ($we_have_config && $globalSettings[CALIBRE_DIR] === '') {
		if ($app->request()->isPost() && $app->request()->getResourceUri() === '/admin/') {
			# let go through
		} else {
			$app->getLog()->warn('Calibre library path not configured, showing admin page.');	
			$app->redirect($app->request()->getRootUri().'/admin');
		}
	}

	# Setup the connection to the Calibre metadata db
	$clp = $globalSettings[CALIBRE_DIR].'/metadata.db';
	$bbs->openCalibreDB($clp);
	if (!$bbs->libraryOk()) {
		$app->getLog()->error('Exception while opening metadata db '.$clp.'. Showing admin page.');	
		$app->redirect($app->request()->getRootUri().'/admin');
	} 	
	$app->getLog()->debug("check_config end");
}

function myNotFound() {
	global $app, $globalSettings;
	$app->render('error.html', array(
		'page' => mkPage($globalSettings['langa']['not_found1']), 
		'title' => $globalSettings['langa']['not_found1'], 
		'error' => $globalSettings['langa']['not_found2']));
}

# Index page -> /
function main() {
	global $app, $globalSettings, $bbs;

	$books = $bbs->last30Books();
	$app->render('index_last30.html',array(
		'page' => mkPage($globalSettings['langa']['dl30'],1), 
		'books' => $books));	
}

# Admin page -> /admin/
function admin() {
	global $app, $globalSettings, $bbs;

	$app->render('admin.html',array(
		'page' => mkPage($globalSettings['langa']['admin'])));
}

# Is the key in globalSettings?
function has_global_setting($key) {
	return (isset($globalSettings[$key]) && !empty($globalSettings[$key]));
}

# Is there a valid - existing - Calibre directory?
function has_valid_calibre_dir() {
	return (has_global_setting(CALIBRE_DIR) && 
		BicBucStriim::checkForCalibre($globalSettings[CALIBRE_DIR]));
}

# Processes changes in the admin page -> POST /admin/
function admin_change_json() {
	global $app, $globalSettings, $bbs;
	$app->getLog()->debug('admin_change: started');	
	$nconfigs = array();
	$req_configs = $app->request()->post();
	$errors = array();

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

	# Don't save just return the error status
	if (count($errors) > 0) {
		$app->getLog()->error('admin_change: ended with error '.var_export($errors, true));	
		$app->render('admin_status.html',array(
		'page' => mkPage($globalSettings['langa']['admin']), 
		'status_ok' => false,
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
		$app->render('admin_status.html',array(
			'page' => mkPage($globalSettings['langa']['admin']), 
			'status_ok' => true));	
	}
}

# Checks access to the admin page -> /admin/access/check
function admin_checkaccess() {
	global $app, $globalSettings, $bbs;

	$app->deleteCookie(ADMIN_COOKIE);
	$password = $app->request()->post('admin_pwin');
	$app->getLog()->debug('admin_checkaccess input: '.$password);

	$response = $app->response();
	$response['Content-Type'] = 'application/json';
	$response['X-Powered-By'] = 'Slim';
	$response->status(200);
	if ($password == $globalSettings[ADMIN_PW]) {
		$app->getLog()->debug('admin_checkaccess succeded');
		$app->setCookie(ADMIN_COOKIE,$password);
		$answer = array('access' => true);
	} else {		
		$app->getLog()->debug('admin_checkaccess failed');
		$answer = array('access' => false, 'message' => $globalSettings['langa']['invalid_password']);
	}
	$response->body(json_encode($answer));
}

# Check if the admin page is protected by a password
# -> /admin/access/
function admin_is_protected() {
	global $app, $globalSettings;

	if (!empty($globalSettings[ADMIN_PW])) {
		$app->getLog()->debug('admin_is_protected: yes');
		$app->response()->status(200);
		$app->response()->body('1');
	} else {		
		$app->getLog()->debug('admin_is_protected: no');
		$app->response()->status(200);
		$app->response()->body('0');
	}	
}


# A list of all titles -> /titles/
function titles() {
	global $app, $globalSettings, $bbs;

	$grouped_books = $bbs->allTitles();
	$app->render('titles.html',array(
		'page' => mkPage($globalSettings['langa']['titles'],2), 
		'books' => $grouped_books));
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

	$app->render('title_detail.html',
		array('page' => mkPage($globalSettings['langa']['book_details']), 
			'calibre_dir' => $calibre_dir,
			'book' => $details['book'], 
			'authors' => $details['authors'], 
			'tags' => $details['tags'], 
			'formats'=>$details['formats'], 
			'comment' => $details['comment'],
			'protect_dl' => is_protected($id))
	);
}

# Show the password dialog
# Route: /titles/:id/showaccess/
function showaccess($id) {
	global $app, $globalSettings;

	$app->render('password_dialog.html',
		array('page' => mkPage($globalSettings['langa']['check_access'],0,true), 
					'bookid' => $id));
}

# Check the access rights for a book and set a cookie if successful.
# Sends 404 if unsuccessful.
# Route: /titles/:id/checkaccess/
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

	if ($globalSettings[GLOB_DL_CHOICE] == "1") 
		$cpw = $globalSettings[ADMIN_PW];
	elseif ($globalSettings[GLOB_DL_CHOICE] == "2") 
		$cpw = $globalSettings[GLOB_DL_PASSWORD];

	if ($password == $cpw) {
		$app->getLog()->debug('checkaccess succeded');
		$app->setCookie(GLOBAL_DL_COOKIE,$cpw);
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
	global $app, $calibre_dir, $bbs;

	$has_cover = false;
	$rot = $app->request()->getRootUri();
	$book = $bbs->title($id);
	if (is_null($book)) {
		$app->getLog()->error("thumbnail: book not found: "+$id);
		$app->response()->status(404);
		return;
	}
	
	if ($book->has_cover) {		
		$thumb = $bbs->titleThumbnail($id);
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
		$app->getLog()->warn("book: attempt to download a protected book, "+$id);		
		$app->response()->status(404);	
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
	header("Content-type: ".getMimeType($bookpath));
	readfile_chunked($bookpath);
}

# List of all authors -> /authors
function authors() {
	global $app, $globalSettings, $bbs;

	$grouped_authors = $bbs->allAuthors();		
	$app->render('authors.html',array(
		'page' => mkPage($globalSettings['langa']['authors'],3), 
		'authors' => $grouped_authors));
}

# Details for a single author -> /authors/:id
function author($id) {
	global $app, $globalSettings, $bbs;

	$details = $bbs->authorDetails($id);
	if (is_null($details)) {
		$app->getLog()->debug("no author");
		$app->notFound();		
	}
	$app->render('author_detail.html',array(
		'page' => mkPage($globalSettings['langa']['author_details']), 
		'author' => $details['author'], 
		'books' => $details['books']));
}

#List of all tags -> /tags
function tags() {
	global $app, $globalSettings, $bbs;

	$grouped_tags = $bbs->allTags();
	$app->render('tags.html',array(
		'page' => mkPage($globalSettings['langa']['tags'],4),
		'tags' => $grouped_tags));
}

#Details of a single tag -> /tags/:id
function tag($id) {
	global $app, $globalSettings, $bbs;

	$details = $bbs->tagDetails($id);
	if (is_null($details)) {
		$app->getLog()->debug("no tag");
		$app->notFound();		
	}
	$app->render('tag_detail.html',array('page' => mkPage($globalSettings['langa']['tag_details']), 
		'tag' => $details['tag'], 
		'books' => $details['books']));
}



#####
##### Utility and helper functions, private
#####


# Try to find the correct mime type for a book file.
function getMimeType($file_path) {
	$mtype = '';
	
	if (preg_match('/epub$/',$file_path) == 1)
		return 'application/epub+zip';
	else if (preg_match('/mobi$/', $file_path) == 1) 
		return 'application/x-mobipocket-ebook';

	if (function_exists('mime_content_type')){
    	     $mtype = mime_content_type($file_path);
  }
	else if (function_exists('finfo_file')){
    	     $finfo = finfo_open(FILEINFO_MIME);
    	     $mtype = finfo_file($finfo, $file_path);
    	     finfo_close($finfo);  
  }
	if ($mtype == ''){
    	     $mtype = "application/force-download";
  }
	return $mtype;
}

# Check whether the book download must be protected. 
# Returns:
#  true - the user must enter a password
#  false - no password necessary
#
function is_protected($id) {
	global $app, $globalSettings;

	# Get the cookie
	# TBD more checks
	$glob_dl_cookie = $app->getCookie(GLOBAL_DL_COOKIE);
	if (isset($glob_dl_cookie)) {
		$app->getLog()->debug('is_protected: Cookie glob_dl_access value: '.$glob_dl_cookie);		
	} else {
		$app->getLog()->debug('is_protected: No cookie glob_dl_access');		
	}
	if ($globalSettings[GLOB_DL_CHOICE] != "0" && is_null($glob_dl_cookie))
		return true;
	else 
		return false;
}


# Utility function to fill the page array
function mkPage($subtitle='', $menu=0, $dialog=false) {
	global $app, $globalSettings;

	if ($subtitle == '') 
		$title = $globalSettings['appname'];
	else
		$title = $globalSettings['appname'].$globalSettings['sep'].$subtitle;
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
 * getUserLangs()
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

#Utility function to server files
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

?>
