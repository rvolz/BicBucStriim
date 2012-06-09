<?php


class Item {}
class Book extends Item {}
class Author extends Item {}
class BookAuthorLink extends Item {}
class BookTagLink extends Item {}
class Tag extends Item {}
class Data extends Item {}
class Comment extends Item {}

class Config extends Item{}

class BicBucStriim {
	const DBNAME = 'data/data.db';
	var $mydb = NULL;
	var $calibre = NULL;
	var $calibre_dir = '';
	var $last_error = 0;

	function __construct() {
		if (file_exists(self::DBNAME) && is_writeable(self::DBNAME)) {
			$this->mydb = new PDO('sqlite:'.self::DBNAME, NULL, NULL, array());
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
		$authors = $this->find('Author','select * from authors order by sort');		
		return $this->mkInitialedList($authors);
	}

	# Return a grouped list of all tags. The list is separated by dividers, 
	# the initial character.
	function allTags() {
		$tags = $this->find('Tag','select * from tags order by name');		
		return $this->mkInitialedList($tags);
	}

	# Return a grouped list of all books. The list is separated by dividers, 
	# the initial title character.
	function allTitles() {
		$books = $this->find('Book','select * from books order by sort');		
		return $this->mkInitialedList($books);
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


	# Find a single book, its authors, tags, formats and comment.
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
