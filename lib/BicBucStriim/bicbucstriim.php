<?php

require_once 'utilities.php';

class BicBucStriim {
	# Name to the bbs db
	const DBNAME = 'data.db';
	# Thumbnail dimension (they are square)
	const THUMB_RES = 160;

	# bbs sqlite db
	var $mydb = NULL;
	# calibre sqlite db
	var $calibre = NULL;
	# calibre library dir
	var $calibre_dir = '';
	# calibre library file, last modified date
	var $calibre_last_modified;
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
			$this->calibre_last_modified = filemtime($rp);
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

	# Return a slice of entries defined by the parameters $index and $length.
	# If $search is defined it is used to filter the titles, ignoring case.
	# Return an array with elements: current page, no. of pages, $length entries
	function findSlice($class, $index=0, $length=100, $search=NULL) {
		if ($index < 0 || $length < 1 || !in_array($class, array('Book','Author','Tag')))
			return array('page'=>0,'pages'=>0,'entries'=>NULL);
		$offset = $index * $length;		
		switch($class) {
			case 'Author': 
				if (is_null($search)) {
					$count = 'select count(*) from authors';
					$query = 'select a.id, a.name, a.sort, count(bal.id) as anzahl from authors as a left join books_authors_link as bal on a.id = bal.author group by a.id order by a.sort limit '.$length.' offset '.$offset;
				}	else {
					$count = 'select count(*) from authors where lower(sort) like \'%'.strtolower($search).'%\'';
					$query = 'select a.id, a.name, a.sort, count(bal.id) as anzahl from authors as a left join books_authors_link as bal on a.id = bal.author where lower(a.name) like \'%'.strtolower($search).'%\' group by a.id order by a.sort limit '.$length.' offset '.$offset;	
				}
				break;
			case 'Book': 
				if (is_null($search)) {
					$count = 'select count(*) from books';
					$query = 'select * from books order by sort limit '.$length.' offset '.$offset;
				}	else {
					$count = 'select count(*) from books where lower(title) like \'%'.strtolower($search).'%\'';
					$query = 'select * from books where lower(title) like \'%'.strtolower($search).'%\' order by sort limit '.$length.' offset '.$offset;	
				}
				break;
			case 'Tag': 
				if (is_null($search)) {
					$count = 'select count(*) from tags';
					$query = 'select tags.id, tags.name, count(btl.id) as anzahl from tags left join books_tags_link as btl on tags.id = btl.tag group by tags.id order by tags.name limit '.$length.' offset '.$offset;
				}	else {
					$count = 'select count(*) from tags where lower(name) like \'%'.strtolower($search).'%\'';
					$query = 'select tags.id, tags.name, count(btl.id) as anzahl from tags left join books_tags_link as btl on tags.id = btl.tag where lower(tags.name) like \'%'.strtolower($search).'%\' group by tags.id order by tags.name limit '.$length.' offset '.$offset;	
				}
				break;
		}
		$no_entries = $this->count($count);
		$no_pages = (int) ($no_entries / $length);
		if ($no_entries % $length > 0)
			$no_pages += 1;
		$entries = $this->find($class,$query);
		return array('page'=>$index, 'pages'=>$no_pages, 'entries'=>$entries);
	}

	# Return the number (int) of rows for a SQL COUNT Statement, e.g.
	# SELECT COUNT(*) FROM books;
	function count($sql) {
		$result = $this->calibre->query($sql)->fetchColumn(); 
		if ($result == NULL || $result == FALSE)
			return -1;
		else
			return (int) $result;
	}


	# Return the 30 most recent books
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

	# Search a list of authors defined by the parameters $index and $length.
	# If $search is defined it is used to filter the names, ignoring case.
	# Return an array with elements: current page, no. of pages, $length entries
	function authorsSlice($index=0, $length=100, $search=NULL) {
		return $this->findSlice('Author', $index, $length, $search);
	}

	# Return a grouped list of all tags. The list is separated by dividers, 
	# the initial character.
	function allTags() {
		#$tags = $this->find('Tag','select * from tags order by name');		
		$tags = $this->find('Tag', 'select tags.id, tags.name, count(btl.id) as anzahl from tags left join books_tags_link as btl on tags.id = btl.tag group by tags.id order by tags.name;');
		return $this->mkInitialedList($tags);
	}

	# Search a list of tags defined by the parameters $index and $length.
	# If $search is defined it is used to filter the tag names, ignoring case.
	# Return an array with elements: current page, no. of pages, $length entries
	function tagsSlice($index=0, $length=100, $search=NULL) {
		return $this->findSlice('Tag', $index, $length, $search);
	}

	# Return a grouped list of all books. The list is separated by dividers, 
	# the initial title character.
	function allTitles() {
		$books = $this->find('Book','select * from books order by sort');
		return $this->mkInitialedList($books);
	}

	# Search a list of books defined by the parameters $index and $length.
	# If $search is defined it is used to filter the book title, ignoring case.
	# Return an array with elements: current page, no. of pages, $length entries
	function titlesSlice($index=0, $length=100, $search=NULL) {
		return $this->findSlice('Book', $index, $length, $search);
	}

	# Find a single author and return the details plus all books.
	function authorDetails($id) {
		$author = $this->findOne('Author', 'select * from authors where id='.$id);
		if (is_null($author)) return NULL;
		$book_ids = $this->find('BookAuthorLink', 'select * from books_authors_link where author='.$id);
		$books = array();
		foreach($book_ids as $bid) {
			$book = $this->title($bid->book);
			array_push($books, $book);
		}
		return array('author' => $author, 'books' => $books);
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
			return Utilities::bookPath($this->calibre_dir,$book->path,'cover.jpg');
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

	# 
	/**
	 * Find a single book, its authors, tags, formats and comment.
	 * @param  int 		$id 	the Calibre book ID
	 * @return array     		the book and its authors, tags, formats, and comment/description
	 */
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
		$formats = $this->find('Data', 'select * from data where book='.$id);
		$comment = $this->findOne('Comment', 'select * from comments where book='.$id);
		if (is_null($comment))
			$comment_text = '';
		else
			$comment_text = $comment->text;		
		return array('book' => $book, 'authors' => $authors, 'tags' => $tags, 
			'formats' => $formats, 'comment' => $comment_text);
	}

	/**
	 * Find a subset of the details for a book that is sufficient for an OPDS 
	 * partial acquisition feed. The function assumes that the book record has 
	 * already been loaded.
	 * @param  Book   $book complete book record from title()
	 * @return array       	the book and its authors, tags and formats
	 */
	function titleDetailsOpds($book) {
		if (is_null($book)) return NULL;
		$author_ids = $this->find('BookAuthorLink', 'select * from books_authors_link where book='.$book->id);
		$authors = array();
		foreach($author_ids as $aid) {
			$author = $this->findOne('Author', 'select * from authors where id='.$aid->author);
			array_push($authors, $author);
		}
		$tag_ids = $this->find('BookTagLink', 'select * from books_tags_link where book='.$book->id);
		$tags = array();
		foreach($tag_ids as $tid) {
			$tag = $this->findOne('Tag', 'select * from tags where id='.$tid->tag);
			array_push($tags, $tag);
		}
		$formats = $this->find('Data', 'select * from data where book='.$book->id);
		return array('book' => $book, 'authors' => $authors, 'tags' => $tags, 
			'formats' => $formats, 'comment' => $comment_text);
	}

	# Returns the path to the cover image of a book or NULL.
	function titleFile($id, $file) {
		$book = $this->title($id);
		if (is_null($book)) 
			return NULL;
		else 
			return Utilities::bookPath($this->calibre_dir,$book->path,$file);
	}

	/**
	 * Return the MIME type for an ebook file. 
	 *
	 * To reduce search time the function checks first wether the file 
	 * has a well known extension. If not two functions are tried. If all fails
	 * 'application/force-download' is returned to force the download of the 
	 * unknown format.
	 * 
	 * @param  string $file_path path to ebook file
	 * @return string            MIME type
	 */
	function titleMimeType($file_path) {
		$mtype = '';
		
		if (preg_match('/epub$/',$file_path) == 1)
			return 'application/epub+zip';
		else if (preg_match('/mobi$/', $file_path) == 1) 
			return 'application/x-mobipocket-ebook';
		else if (preg_match('/azw$/', $file_path) == 1) 
			return 'application/vnd.amazon.ebook';
		else if (preg_match('/pdf$/', $file_path) == 1) 
			return 'application/pdf';
		else if (preg_match('/txt$/', $file_path) == 1) 
			return 'text/plain';
		else if (preg_match('/html$/', $file_path) == 1) 
			return 'text/html';
		else if (preg_match('/zip$/', $file_path) == 1) 
			return 'application/zip';

		if (function_exists('mime_content_type')){
	    	     $mtype = mime_content_type($file_path);
	  }
		else if (function_exists('finfo_file')){
	    	     $finfo = finfo_open(FILEINFO_MIME);
	    	     $mtype = finfo_file($finfo, $file_path);
	    	     finfo_close($finfo);  
	  }
		if ($mtype == ''){
	    	     $mtype = 'application/force-download';
	  }
		return $mtype;
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
