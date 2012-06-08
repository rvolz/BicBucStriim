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
require_once 'config.php';

# Allowed languages, i.e. languages with translations
$allowedLangs = array('de','en');
# Fallback language if the browser prefers other than the allowed languages
$fallbackLang = 'en';
# Application Name
$appname = 'BicBucStriim';
# App version
$appversion = '0.8.0';
# Cookie name for global download protection
define('GLOBAL_DL_COOKIE', 'glob_dl_access');



# Init app and routes
$app = new Slim(array(
	'debug' => true,
	'log.enabled' => true, 
	#'log.writer' => new Slim_LogFileWriter(fopen('./logs/bbs.log','a')),
	'log.level' => 4,
	'view' => new TwigView(),
	));

$globalSettings = array();
$globalSettings['appname'] = $appname;
$globalSettings['version'] = $appversion;
$globalSettings['sep'] = ' :: ';
$globalSettings['lang'] = getUserLang($allowedLangs, $fallbackLang);
if ($globalSettings['lang'] == 'de')
	$globalSettings['langa'] = $langde;
else
	$globalSettings['langa'] = $langen;
$globalSettings['glob_dl_toggle'] = isset($glob_dl_toggle) ? $glob_dl_toggle : false;
#$app->getLog()->debug('Global Download Toggle: '.$globalSettings['glob_dl_toggle']);	
$globalSettings['glob_dl_password'] = isset($glob_dl_password) ? $glob_dl_password : '7094e7dc2feb759758884333c2f4a6bdc9a16bb2';
#$app->getLog()->debug('Global Download Password: '.$globalSettings['glob_dl_password']);	

$app->notFound('myNotFound');
$app->get('/', 'main');
$app->get('/titles/', 'titles');
$app->get('/titles/:id/', 'title');
$app->get('/titles/:id/showaccess/', 'showaccess');
$app->post('/titles/:id/checkaccess/', 'checkaccess');
$app->get('/titles/:id/cover/', 'cover');
$app->get('/titles/:id/file/:file', 'book');
$app->get('/authors/', 'authors');
$app->get('/authors/:id/', 'author');
$app->get('/tags/', 'tags');
$app->get('/tags/:id/', 'tag');

# Setup the connection to the Calibre metadata db
$bbs = new BicBucStriim($calibre_dir.'/'.$metadata_db);
if (!$bbs->libraryOk()) {
	$app->getLog()->error('Exception while opening metadata db '.$calibre_dir.'/'.$metadata_db);	
	$app->render('error.html', array(
		'page' => mkPage($globalSettings['langa']['error']), 
		'title' => $globalSettings['langa']['error'], 
		'error' => $globalSettings['langa']['mdb_error'].$calibre_dir.'/'.$metadata_db));
} else {
	$app->run();
}

function myNotFound() {
	global $app;
	global $globalSettings;
	$app->render('error.html', array(
		'page' => mkPage($globalSettings['langa']['not_found1']), 
		'title' => $globalSettings['langa']['not_found1'], 
		'error' => $globalSettings['langa']['not_found2']));
}

# Index page -> /
function main() {
	global $app, $bbs;

	$books = $bbs->last30Books();
	$app->render('index_last30.html',array('page' => mkPage(), 'books' => $books));	
}

# A list of all titles -> /titles/
function titles() {
	global $app;
	global $globalSettings;
	global $bbs;

	$grouped_books = $bbs->allTitles();
	$app->render('titles.html',array('page' => mkPage($globalSettings['langa']['titles']), 'books' => $grouped_books));
}

# Show a single title > /titles/:id. The ID ist the Calibre ID
function title($id) {
	global $app;
	global $calibre_dir;
	global $globalSettings;
	global $bbs;
	
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
	global $app;
	global $globalSettings;

	$app->render('password_dialog.html',
		array('page' => mkPage($globalSettings['langa']['check_access']), 
					'bookid' => $id));
}

# Check the access rights for a book and set a cookie if successful.
# Sends 404 if unsuccessful.
# Route: /titles/:id/checkaccess/
function checkaccess($id) {
	global $app;
	global $calibre_dir;
	global $globalSettings;
	global $bbs;

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
	if ($password == $globalSettings['glob_dl_password']) {
		$app->getLog()->debug('checkaccess succeded');
		$app->setCookie(GLOBAL_DL_COOKIE,$password);
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
	global $app;
	global $calibre_dir;
	global $bbs;

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

# Return the selected file for the book with ID. 
# Route: /titles/:id/file/:file
function book($id, $file) {
	global $app;
	global $bbs;

	$book = $bbs->title($id);
	if (is_null($book)) {
		$app->getLog()->debug("no book file");
		$app->notFound();
	}	
	if (is_protected($id)) {
		$app->getLog()->warning("book: attempt to download a protected book, "+$id);		
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
	global $app;
	global $globalSettings;
	global $bbs;

	$grouped_authors = $bbs->allAuthors();		
	$app->render('authors.html',array( 'page' => mkPage($globalSettings['langa']['authors']), 'authors' => $grouped_authors));
}

# Details for a single author -> /authors/:id
function author($id) {
	global $app;
	global $globalSettings;
	global $bbs;

	$details = $bbs->authorDetails($id);
	if (is_null($details)) {
		$app->getLog()->debug("no author");
		$app->notFound();		
	}
	$app->render('author_detail.html',array('page' => mkPage($globalSettings['langa']['author_details']), 
		'author' => $details['author'], 
		'books' => $details['books']));
}

#List of all tags -> /tags
function tags() {
	global $app;
	global $globalSettings;
	global $bbs;

	$grouped_tags = $bbs->allTags();
	$app->render('tags.html',array('page' => mkPage($globalSettings['langa']['tags']),'tags' => $grouped_tags));
}

#Details of a single tag -> /tags/:id
function tag($id) {
	global $app;
	global $globalSettings;
	global $bbs;

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
	global $app;
	global $globalSettings;
	global $global_dl_cookie_name;

	# Get the cookie
	# TBD more checks
	$glob_dl_cookie = $app->getCookie(GLOBAL_DL_COOKIE);
	if (isset($glob_dl_cookie)) {
		$app->getLog()->debug('is_protected: Cookie glob_dl_access value: '.$glob_dl_cookie);		
	} else {
		$app->getLog()->debug('is_protected: No cookie glob_dl_access');		
	}
	if ($globalSettings['glob_dl_toggle'] && !isset($glob_dl_cookie))
		return true;
	else 
		return false;
}


# Utility function to fill the page array
function mkPage($subtitle='') {
	global $app;
	global $globalSettings;

	if ($subtitle == '') 
		$title = $globalSettings['appname'];
	else
		$title = $globalSettings['appname'].$globalSettings['sep'].$subtitle;
	$rot = $app->request()->getRootUri();
	$page = array('title' => $title, 
		'rot' => $rot,
		'h1' => $subtitle,
		'version' => $globalSettings['version'],
		'glob' => $globalSettings);
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
