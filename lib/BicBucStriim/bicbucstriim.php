<?php

class Item {}
class Book extends Item {}
class Author extends Item {}
class BookAuthorLink extends Item {}
class BookTagLink extends Item {}
class Tag extends Item {}
class Data extends Item {}
class Comment extends Item {}
class Series extends Item {}
class BookSeriesLink extends Item {}
class Config extends Item{}

class BicBucStriim {
	# Name to the bbs db
	const DBNAME = 'data.db';
	# Thumbnail dimension (they are square)
	const THUMB_RES = 160;

	# bbs sqlite db
	var $mydb = NULL;
	# calibre sqlite db
	var $calibre = NULL;
	# calinbre library dir
	var $calibre_dir = '';
	# last sqlite error
	var $last_error = 0;
	# dir for bbs db
	var $data_dir = '';
	# dir for generated thumbs
	var $thumb_dir = '';

	static function checkForCalibre($path) {
		$rp = realpath($path);
		$rpm = $rp.'/metadata.db';
		return is_readable($rpm);
	}

	# Open the BBS DB. The thumbnails are stored in the same directory as the db.
	function __construct($dataPath='data/data.db') {
		$rp = realpath($dataPath);
		$this->data_dir = dirname($dataPath);
		$this->thumb_dir = $this->data_dir;
		if (file_exists($rp) && is_writeable($rp)) {
			$this->mydb = new PDO('sqlite:'.$rp, NULL, NULL, array());
			$this->mydb->setAttribute(1002, 'SET NAMES utf8');
			$this->mydb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->mydb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			$this->last_error = $this->mydb->errorCode();
		} else {
			$this->mydb = NULL;
		}
	}

	function openCalibreDB($calibrePath) {
		$rp = realpath($calibrePath);
		$this->calibre_dir = dirname($rp);
		if (file_exists($rp) && is_readable($rp)) {
			$this->calibre = new PDO('sqlite:'.$rp, NULL, NULL, array());
			$this->calibre->setAttribute(1002, 'SET NAMES utf8');
			$this->calibre->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->calibre->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			$this->last_error = $this->calibre->errorCode();
		} else {
			$this->calibre = NULL;
		}
	}

	# Is our own DB open?
	function dbOk() {
		return (!is_null($this->mydb));
	}

	# Execute a query $sql on the settings DB and return the 
	# result as an array of objects of class $class
	function sfind($class, $sql) {
		$stmt = $this->mydb->query($sql,PDO::FETCH_CLASS, $class);		
		$this->last_error = $stmt->errorCode();
		$items = $stmt->fetchAll();
		$stmt->closeCursor();	
		return $items;
	}

	function configs() {
		return $this->sfind('Config','select * from configs');	
	}
	function saveConfigs($configs) {
		$sql = 'update configs set val=:val where name=:name';
		$stmt = $this->mydb->prepare($sql);
		$this->mydb->beginTransaction();
		#$this->mydb->exec('delete from configs');
		foreach ($configs as $config) {
			$stmt->execute(array('name' => $config->name, 'val' => $config->val));
		}
		$this->mydb->commit();
	}

	############# Calibre DB functions ################

	# Is the Calibre library open?
	function libraryOk() {
		return (!is_null($this->calibre));
	}

	# Execute a query $sql on the Calibre DB and return the 
	# result as an array of objects of class $class
	function find($class, $sql) {
		$stmt = $this->calibre->query($sql,PDO::FETCH_CLASS, $class);		
		$this->last_error = $stmt->errorCode();
		$items = $stmt->fetchAll();
		$stmt->closeCursor();	
		return $items;
	}

	# Return a single object or NULL if not found
	function findOne($class, $sql) {
		$result = $this->find($class, $sql);
		if ($result == NULL || $result == FALSE)
			return NULL;
		else
			return $result[0];
	}

	function last30Books() {
		$books = $this->find('Book','select * from books order by timestamp desc limit 30');		
		return $books;
	}

	# Return a grouped list of all authors. The list is separated by dividers, 
	# the initial name character.
	function allAuthors() {
		#$authors = $this->find('Author','select * from authors order by sort');		
		$authors = $this->find('Author', 'select a.id, a.name, a.sort, count(bal.id) as anzahl from authors as a left join books_authors_link as bal on a.id = bal.author group by a.id order by a.sort');
		return $this->mkInitialedList($authors);
	}
	
	# Return a grouped list of all tags. The list is separated by dividers,
  # the initial character.
  function allTags() {
    #$tags = $this->find('Tag','select * from tags order by name');
    $tags = $this->find('Tag', 'select tags.id, tags.name, count(btl.id) as anzahl from tags left join books_tags_link as btl on tags.id = btl.tag group by tags.id order by tags.name;');
    return $this->mkInitialedList($tags);
  }
	

	# Return a grouped list of all series. The list is separated by dividers, 
	# the initial character.
	function allSeries() {
		#$tags = $this->find('Tag','select * from series order by name');		
		$series = $this->find('Series', 'select series.id, series.name, count(bsl.id) as anzahl from series left join books_series_link as bsl on series.id = bsl.series group by series.id order by series.name;');
				return $this->mkInitialedList($series);
	}

	# Return a grouped list of all books. The list is separated by dividers, 
	# the initial title character.
	function allTitles() {
		$books = $this->find('Book','select * from books order by sort');		
		return $this->mkInitialedList($books);
	}
	
	
	# Return a grouped list of all books sorted by series.
	function allSortedTitles() {
    $series_ids = $this->find('Series', 'select series.id from series left join books_series_link as bsl on series.id = bsl.series group by series.id order by series.name');
	 	$booksInSeries = array();
	 	foreach ($series_ids as $sid)
	 	{
      $books = $this->seriesDetails($sid->id);
      unset($books['series']);
      $booksInSeries = array_merge_recursive($booksInSeries, $books);  
    }
    $booksInSeries = $booksInSeries['books'];
    $books = $this->find('Book', 'select * from books where books.id not in (select book from books_series_link) order by sort'); 
	  $groupedBooks = array_merge($booksInSeries, $books);
	  return $groupedBooks;
  }
		

	# Find a single author and return the details plus all books.
	function authorDetails($id) {
		$author = $this->findOne('Author', 'select * from authors where id='.$id);
		if (is_null($author)) return NULL;
		$book_ids = $this->find('BookAuthorLink', 'select * from books_authors_link where author='.$id);
		$books = array();
		foreach($book_ids as $bid) 
		{
			$book = $this->title($bid->book);
			array_push($books, $book);
		}
		return array('author' => $author, 'books' => $books);
	}

	# Return the true path of a book. Works around a strange feature of Calibre 
	# where middle components of names are capitalized, eg "Aliette de Bodard" -> "Aliette De Bodard".
	# The directory name uses the capitalized form, the book path stored in the DB uses the original form.
	# Legacy problem?
	function bookPath($cd, $bp, $file) {
		try {
			$path = $cd.'/'.$bp.'/'.$file;
			stat($path);
		} catch (Exception $e) {
			$p = explode("/",$bp);
			$path = $cd.'/'.ucwords($p[0]).'/'.$p[1].'/'.$file;
		}
		return $path;
	}
	
	# Returns a tag and the related books
  function tagDetails($id) {
    $tag = $this->findOne('Tag', 'select * from tags where id='.$id);
    if (is_null($tag)) return NULL;
    $book_ids = $this->find('BookTagLink', 'select * from books_tags_link where tag='.$id);
    $books = array();
    foreach($book_ids as $bid) {
      $book = $this->title($bid->book);
      array_push($books, $book);
    }
    return array('tag' => $tag, 'books' => $books);
  }
	
	

	# Returns a series and the related books
	function seriesDetails($id) {
		$series = $this->findOne('Series', 'select * from series where id='.$id);
		if (is_null($series)) return NULL;
		$books = $this->find('Book', 'select BSL.book, Books.* from books_series_link BSL, books Books where Books.id=BSL.book and series='.$id.' order by series_index');
		return array('series' => $series, 'books' => $books);
	}


	# Find only one book
	function title($id) {
		return $this->findOne('Book','select * from books where id='.$id);
	}

	# Returns the path to the cover image of a book or NULL.
	function titleCover($id) {
		$book = $this->title($id);
		if (is_null($book)) 
			return NULL;
		else
			return $this->bookPath($this->calibre_dir,$book->path,'cover.jpg');
	}

	# Returns the path to a thumbnail of a book's cover image or NULL. 
	# If a thumbnail doesn't exist the function tries to make one from the cover.
	# The thumbnail dimension generated is 160*160, which is more than what 
	# jQuery Mobile requires (80*80). However, if we send the 80*80 resolution the 
	# thumbnails look very pixely.
	#
	function titleThumbnail($id) {
		$thumb_name = 'thumb_'.$id.'.png';
		$thumb_path = $this->thumb_dir.'/'.$thumb_name;
		$newwidth = self::THUMB_RES;
		$newheight = self::THUMB_RES;
		if (!file_exists($thumb_path)) {
			$cover = $this->titleCover($id);
			if (is_null($cover))
				$thumb_path = NULL;
			else {
				list($width, $height) = getimagesize($cover);
				$thumb = imagecreatetruecolor($newwidth, $newheight);
				$source = imagecreatefromjpeg($cover);
				$minwh = min(array($width, $height));
				$newx = ($width / 2) - ($minwh / 2);
				$newy = ($height / 2) - ($minwh / 2);
				$inbetween = imagecreatetruecolor($minwh, $minwh);
				imagecopy($inbetween, $source, 0, 0, $newx, $newy, $minwh, $minwh);				
				imagecopyresized($thumb, $inbetween, 0, 0, 0, 0, $newwidth, $newheight, $minwh, $minwh);
				$created = imagepng($thumb, $thumb_path);				
			}
		}
		return $thumb_path;
	}

	# Find a single book, its authors, tags, series, formats and comment.
	function titleDetails($id) {
		$book = $this->title($id);
		if (is_null($book)) return NULL;
		$author_ids = $this->find('BookAuthorLink', 'select * from books_authors_link where book='.$id);
		$authors = array();
		foreach($author_ids as $aid) {
			$author = $this->findOne('Author', 'select * from authors where id='.$aid->author);
			array_push($authors, $author);
		}
		$tag_ids = $this->find('BookTagLink', 'select * from books_tags_link where book='.$id);
		$tags = array();
		foreach($tag_ids as $tid) {
			$tag = $this->findOne('Tag', 'select * from tags where id='.$tid->tag);
			array_push($tags, $tag);
		}
		$series_ids = $this->find('BookSeriesLink', 'select * from books_series_link where book='.$id);
		$series = array();
		foreach($series_ids as $sid) {
			$oneSeries = $this->findOne('Series', 'select * from series where id='.$sid->series);
			array_push($series, $oneSeries);
		}
		
		$formats = $this->find('Data', 'select * from data where book='.$id);
		$comment = $this->findOne('Comment', 'select * from comments where book='.$id);
		if (is_null($comment))
			$comment_text = '';
		else
			$comment_text = $comment->text;	      	
		return array('book' => $book, 'authors' => $authors, 'tags' => $tags, 
			'series' => $series, 'formats' => $formats, 'comment' => $comment_text);
	}

	# Returns the path to the cover image of a book or NULL.
	function titleFile($id, $file) {
		$book = $this->title($id);
		if (is_null($book)) 
			return NULL;
		else 
			return $this->bookPath($this->calibre_dir,$book->path,$file);
	}


	# Generate a list where the items are grouped and separated by 
	# the initial character.
	# If the item has a 'sort' field that is used, else the name.
	function mkInitialedList($items) {
		$grouped_items = array();
		$initial_item = "";
		foreach ($items as $item) {
			if (isset($item->sort))
				$is = $item->sort;
			else 
				$is = $item->name;
			$ix = mb_strtoupper(mb_substr($is,0,1,'UTF-8'), 'UTF-8');
			if ($ix != $initial_item) {
				array_push($grouped_items, array('initial' => $ix));
				$initial_item = $ix;
			} 
			array_push($grouped_items, $item);
		}
		return $grouped_items;
	}
}
?>
