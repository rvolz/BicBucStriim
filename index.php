<?php
// Copyight 2012 Rainer Volz
// Licensed under MIT License, see README.MD/License

require 'lib/rb.php';
require_once 'config.php';
require_once 'lib/Slim/Slim.php';
require_once 'lib/Slim/Views/TwigView.php';
TwigView::$twigDirectory = dirname(__FILE__) . '/lib/Twig';
TwigView::$twigExtensions = array(
    'Twig_Extensions_Slim'
);

$allowedLangs = array('de','en');
$fallbackLang = 'en';

$langde = array('home' => "Start",
	'titles' => "Bücher", 
	'book_details' => "Buchdetails",
	'authors' => "Autoren",
	'author_details' => "Details Autor",
	'tags' => "Schlagwörter",
	'booksby' => "Bücher von",
	'dl30' => "Die letzten 30",
	'download' => "Herunterladen",
	'back' => "Zurück",
	'presskey' => 'Taste drücken, um das Buch im betreffenden Format herunter zu laden.',
	'published' => 'Veröffentlicht',
	'comment' => 'Beschreibung',
	'error' => 'Fehler',
	'mdb_error' => 'Calibre Datenbank existiert nicht oder konnte nicht gelesen werden: ');
$langen = array('home' => "Home",
	'titles' => "Books", 
	'book_details' => "Book Details",
	'authors' => "Authors",
	'author_details' => "Author Details",
	'tags' => "Tags",
	'booksby' => "Books by",
	'dl30' => "Most recent 30",
	'download' => "Download",
	'back' => "Back",
	'presskey' => 'Press a button to download the book in the respective format.',
	'published' => 'Published',
	'comment' => 'Description',
	'error' => 'Error',
	'mdb_error' => 'Calibre database not found or not readable: ');

$globalSettings = array();
$globalSettings['appname'] = $appname;
$globalSettings['version'] = '0.6.1';
$globalSettings['sep'] = ' :: ';
$globalSettings['lang'] = getUserLang($allowedLangs, $fallbackLang);
if ($globalSettings['lang'] == 'de')
	$globalSettings['langa'] = $langde;
else
	$globalSettings['langa'] = $langen;

# Init app and routes
$app = new Slim(array(
	'log.enable' => true, 
	'log.path' => './logs',
	'log.level' => 4,
	'view' => new TwigView()));
$app->notFound('myNotFound');
$app->get('/', 'main');
$app->get('/titles/', 'titles');
$app->get('/titles/:id/', 'title');
$app->get('/titles/:id/cover/', 'cover');
$app->get('/titles/:id/file/:file', 'book');
$app->get('/authors/', 'authors');
$app->get('/authors/:id/', 'author');

$app->getLog()->debug("sss");
# Setup the connection to the Calibre metadata db
if (!file_exists($calibre_dir.'/'.$metadata_db) || !is_readable($calibre_dir.'/'.$metadata_db)) {
	$app->getLog()->error('Exception while opening metadata db '.$calibre_dir.'/'.$metadata_db);	
	$app->render('error.html', array('page' => mkPage($globalSettings['langa']['error']), 
		'error' => $globalSettings['langa']['mdb_error'].$calibre_dir.'/'.$metadata_db));
} else {
	R::setup('sqlite:'.$calibre_dir.'/'.$metadata_db);
	$app->run();
}

function myNotFound() {
	global $app;
	$app->render('404.html');
}



# Index page -> /
function main() {
	global $app;

	$books = R::find('books',' 1 ORDER BY timestamp DESC LIMIT 30');		
	$app->render('index_last30.html',array('page' => mkPage(), 'books' => $books));
	R::close();
}

# A list of all titles -> /titles/
function titles() {
	global $app;
	global $globalSettings;

	$books = R::find('books',' 1 ORDER BY sort');
	$grouped_books = array();
	$initial_book = "";
	foreach ($books as $book) {
		$ix = mb_strtoupper(mb_substr($book->sort,0,1,'UTF-8'), 'UTF-8');
		if ($ix != $initial_book) {
			array_push($grouped_books, array('initial' => $ix));
			$initial_book = $ix;
		} 
		array_push($grouped_books, $book);
	}

	$app->render('titles.html',array('page' => mkPage($globalSettings['langa']['titles']), 'books' => $grouped_books));
	R::close();
}

# Show a single title > /titles/:id. The ID ist the Calibre ID
function title($id) {
	global $app;
	global $calibre_dir;
	global $globalSettings;

	$book = R::findOne('books',' id=?', array(intval($id)));
	if (is_null($book)) {
		$app->getLog()->debug("no book");
		$app->notFound();		
	}
	$author_ids = R::find('books_authors_link', ' book=?', array($id));
	$authors = array();
	foreach($author_ids as $aid) {
		$author = R::findOne('authors', ' id=?', array($aid->author));
		array_push($authors, $author);
	}
	$tag_ids = R::find('books_tags_link', ' book=?', array($id));
	$tags = array();
	foreach($tag_ids as $tid) {
		$tag = R::findOne('tags', ' id=?', array($tid->tag));
		array_push($tags, $tag);
	}
	$formats = R::find('data', ' book=?', array($id));
	$comment = R::findOne('comments', 'book=?', array($id));
	if (is_null($comment))
		$comment_text = '';
	else
		$comment_text = $comment->text;
	$app->render('title_detail.html',array('page' => mkPage($globalSettings['langa']['book_details']), 'calibre_dir' => $calibre_dir,'book' => $book, 'authors' => $authors, 'tags' => $tags, 'formats'=>$formats, 'comment' => $comment_text));
	R::close();
}

# Return the cover for the book with ID. Calibre generates only JPEGs, so we always return a JPEG.
# If there is no cover, return the standard cover image.
# Route: /titles/:id/cover
function cover($id) {
	global $app;
	global $calibre_dir;

	$rot = $app->request()->getRootUri();
	$book = R::findOne('books',' id=?', array(intval($id)));
	if (is_null($book)) {
		$app->getLog()->debug("no cover");
		$app->notFound();
	}	
	if ($book->has_cover) {		
		$cover = findBookPath($calibre_dir,$book->path,'cover.jpg');
	} else {
		$cover = 'img/stdcover.jpg';
	}
	R::close();
	$app->response()->status(200);
	$app->response()->header('Content-type','image/jpeg;base64');
	$app->response()->header('Content-Length',filesize($cover));
	#$app->response()->write(base64_encode($cover));
	readfile($cover);
}

# Return the selected file for the book with ID. 
# Route: /titles/:id/file/:file
function book($id, $file) {
	global $app;
	global $calibre_dir;
	global $globalSettings;

	$book = R::findOne('books',' id=?', array(intval($id)));
	if (is_null($book)) {
		$app->getLog()->debug("no book file");
		$app->notFound();
	}	
	$book = findBookPath($calibre_dir, $book->path, $file);
	R::close();
	$app->response()->status(200);
	$app->response()->header('Content-type', getMimeType($book));
	$app->response()->header('Content-Length',filesize($book));
	#$app->response()->write(base64_encode($cover));
	readfile($book);
}

# List of all authors -> /authors
function authors() {
	global $app;
	global $globalSettings;

	$authors = R::find('authors',' 1 ORDER BY sort');		
	$grouped_authors = array();
	$initial_author = "";
	foreach ($authors as $author) {
		$ix = mb_strtoupper(mb_substr($author->sort,0,1,'UTF-8'), 'UTF-8');
		if ($ix != $initial_author) {
			array_push($grouped_authors, array('initial' => $ix));
			$initial_author = $ix;
		} 
		array_push($grouped_authors, $author);
	}
	$app->render('authors.html',array( 'page' => mkPage($globalSettings['langa']['authors']), 'authors' => $grouped_authors));
	R::close();
}

# Details for a single author -> /authors/:id
function author($id) {
	global $app;
	global $globalSettings;

	$author = R::findOne('authors', ' id=?', array($id));
	if (is_null($author)) {
		$app->getLog()->debug("no author");
		$app->notFound();		
	}
	$book_ids = R::find('books_authors_link', ' author=?', array($id));
	$books = array();
	foreach($book_ids as $bid) {
		$book = R::findOne('books', ' id=?', array($bid->book));
		array_push($books, $book);
	}
	$app->render('author_detail.html',array('page' => mkPage($globalSettings['langa']['author_details']), 'author' => $author, 'books' => $books));
	R::close();
}

# Return the true path of a book. Works around a strange feature of Calibre 
# where middle components of names are capitalized, eg "Aliette de Bodard" -> "Aliette De Bodard".
# The directory name uses the capitalized form, the book path stored in the DB uses the original form.
# Legacy problem?
function findBookPath($cd, $bp, $file) {
	global $app;
	try {
		$path = $cd.'/'.$bp.'/'.$file;
		stat($path);
	} catch (Exception $e) {
		$app->getLog()->debug('findBookPath, path not found: '.$path);
		$p = explode("/",$bp);
		$path = $cd.'/'.ucwords($p[0]).'/'.$p[1].'/'.$file;
		$app->getLog()->debug('findBookPath, new path: '.$path);
	}
	return $path;
}

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

# Utulity function to fill the page array
function mkPage($subtitle='') {
	global $app;
	global $globalSettings;

	if ($subtitle == '') 
		$title = $globalSettings['appname'];
	else
		$title = $globalSettings['appname'].$globalSettings['sep'].$subtitle;
	$page = array('title' => $title, 
		'rot' => $app->request()->getRootUri(),
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


?>
