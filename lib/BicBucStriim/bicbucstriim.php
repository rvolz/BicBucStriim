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

	/**
	 * Check if the Calibre DB is readable
	 * @param  string 	$path Path to Calibre DB
	 * @return boolean				true if exists and is readable, else false
	 */
	static function checkForCalibre($path) {
		$rp = realpath($path);
		$rpm = $rp.'/metadata.db';
		return is_readable($rpm);
	}

	/**
	 * Open the BBS DB. The thumbnails are stored in the same directory as the db.
	 * @param string $dataPath Path to BBS DB
	 */
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

	/**
	 * Create an empty BBS DB. The thumbnails are stored in the same directory as the db.
	 * The create DB is empty and must be filled separately.
	 * @param string $dataPath Path to BBS DB
	 */
	function createDataDb($dataPath='data/data.db') {
		$this->mydb = new PDO('sqlite:'.$dataPath, NULL, NULL, array());
		$this->mydb->setAttribute(1002, 'SET NAMES utf8');
		$this->mydb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->mydb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		$this->mydb->exec('create table configs (name varchar(30),val varchar(256))');   
		$this->mydb->exec('create unique index configs_names on configs(name)');
		$this->mydb->exec('create table users (id integer primary key autoincrement, username varchar(30), password char(32), email varchar(256), languages varchar(256), tags (varchar(256)))');   
		$this->mydb->exec('create unique index users_names on users(username)');		
		$mdp = password_hash('admin', PASSWORD_BCRYPT);
		$this->mydb->exec('insert into users (username, password) values ("admin", "'.$mdp.'")');		
		$this->mydb = null;
	}

	/**
	 * Open the Calibre DB
	 * @param  string $calibrePath 	Path to claibre library
	 */
	function openCalibreDB($calibrePath) {
		$rp = realpath($calibrePath);
		$this->calibre_dir = dirname($rp);
		if (file_exists($rp) && is_readable($rp)) {
			$this->calibre_last_modified = filemtime($rp);
			$this->calibre = new PDO('sqlite:'.$rp, NULL, NULL, array());
			$this->calibre->setAttribute(1002, 'SET NAMES utf8');
			$this->calibre->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->calibre->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			$this->last_error = $this->calibre->errorCode();
		} else {
			$this->calibre = NULL;
		}
	}

	/**
	 * Is our own DB open?
	 * @return boolean	true if open, else false
	 */
	function dbOk() {
		return (!is_null($this->mydb));
	}

	/**
	 * Execute a query $sql on the settings DB and return the result 
	 * as an array of objects of class $class
	 * @param  string $class Classname of result objects
	 * @param  string $sql   SQL query
	 * @return array         object sof class $class
	 */
	function sfind($class, $sql) {
		$stmt = $this->mydb->query($sql,PDO::FETCH_CLASS, $class);		
		$this->last_error = $stmt->errorCode();
		$items = $stmt->fetchAll();
		$stmt->closeCursor();	
		return $items;
	}

	/**
	 * Find all configuration values in the settings DB
	 * @return array configuration values
	 */
	function configs() {
		return $this->sfind('Config','select * from configs');	
	}

	/**
	 * Update the DB to version 2 by creating the index necessary for saveConfigs.
	 */
	function updateDbSchema1to2() {
		$this->mydb->exec('create unique index configs_names on configs(name)');
		$this->mydb->exec('update configs set val=\'2\' where name=\'db_version\'');
	}

	/**
	 * Update the DB to version 3.
	 */
	function updateDbSchema1to3() {
		$this->mydb->exec('create table users (id integer primary key autoincrement, 
			username varchar(30), password char(32), 
			email varchar(256), languages varchar(256), tags varchar(256))');   
		$this->mydb->exec('create unique index users_names on users(username)');		
		$mdp = password_hash('admin', PASSWORD_BCRYPT);
		$this->mydb->exec('insert into users (username, password) values ("admin", "'.$mdp.'")');		
		
		$this->mydb->exec('update configs set val=\'3\' where name=\'db_version\'');
	}

	/**
	 * Save all configuration values in the settings DB
	 * @param  $array 	$configs 	configuration values
	 */
	function saveConfigs($configs) {
		$sql = 'insert or replace into configs values (:name, :val)';
		$stmt = $this->mydb->prepare($sql);
		$this->mydb->beginTransaction();
		foreach ($configs as $config) {
			$stmt->execute(array('name' => $config->name, 'val' => $config->val));
		}
		$this->mydb->commit();
	}


/**
	 * Find all user records in the settings DB
	 * @return array user data
	 */
	function users() {
		return $this->sfind('User','select * from users');	
	}

	/**
	 * Find a specific user in the settings DB
	 * @return user data or NULL if not found
	 */
	function user($userid) {
		$users = $this->sfind('User','select * from users where id = '.$userid);	
		if (count($users) == 1)
			return $users[0];
		else
			return NULL;
	}

	/**
	 * Add a new user account.
	 * @param $username string login name for the account, must be unique
	 * @param $password string clear text password 
	 * @return user account or null if there was an error
	 */
	function addUser($username, $password) {
		password_hash($password, PASSWORD_BCRYPT);
		try {
			$this->mydb->exec('insert into users (username, password) values ("'.$username.'", "'.$mdp.'")');   				
			$users = $this->sfind('User','select * from users where username = "'.$username.'"');	
			return $users[0];
		} catch (PDOException $e) {			
			return null;
		}		
	}
	
	/**
	 * Delete a user account from the database. The admin account (ID 1) can't be deleted.
	 * @param $userid integer
	 * @return true if a user was deleted else false
	 */
	function deleteUser($userid) {
		if ($userid == 1)
			return false;
		else {
			$nr = $this->mydb->exec('delete from users where id = '.$userid);   	
			return ($nr > 0);
		}
	}

	/**
	 * Update an exisiting user account. The username cannot be changed.
	 * @param $userid integer 
	 * @param $password string new clear text password or old encrypted password
	 * @param $languages string comma-delimited set of language identifiers
	 * @param $tags string comma-delimited set of tags
	 * @return updated user account or null if there was an error
	 */
	function changeUser($userid, $password, $languages, $tags) {
		$mdp = password_hash($password, PASSWORD_BCRYPT);
		$users = $this->sfind('User','select * from users where id = '.$userid);
		if (count($users) > 0) {
			if ($mdp != $users[0]->password)
				$update_pw = $mdp;
			else
				$update_pw = $users[0]->password;
			try {
				$sql = 'UPDATE users SET password = :password, languages = :languages, tags = :tags WHERE id = :userid';
				$stmt = $this->mydb->prepare($sql);
				$stmt->bindParam(':password', $update_pw);
				$stmt->bindParam(':languages', $languages);
				$stmt->bindParam(':tags', $tags);
				$stmt->bindParam(':userid', $userid);
				$stmt->execute(); 
				$users = $this->sfind('User','select * from users where id = '.$userid);
				return $users[0];
			} catch (PDOException $e) {
				return null; 
			}
		} else {
			return null;
		}
	}
	


	############# Calibre Library functions ################

	/**
	 * Is the Calibre library open?
	 * @return boolean	true if open, else false
	 */
	function libraryOk() {
		return (!is_null($this->calibre));
	}

	/**
	 * Execute a query $sql on the Calibre DB and return the result 
	 * as an array of objects of class $class
	 * 
	 * @param  [type] $class [description]
	 * @param  [type] $sql   [description]
	 * @return [type]        [description]
	 */
	function find($class, $sql) {
		$stmt = $this->calibre->query($sql,PDO::FETCH_CLASS, $class);		
		$this->last_error = $stmt->errorCode();
		$items = $stmt->fetchAll();
		$stmt->closeCursor();	
		return $items;
	}

	/**
	 * Return a single object or NULL if not found
	 * @param  string $class 	[description]
	 * @param  string $sql   	[description]
	 * @return object 				instance of class $class or NULL
	 */
	function findOne($class, $sql) {
		$result = $this->find($class, $sql);
		if ($result == NULL || $result == FALSE)
			return NULL;
		else
			return $result[0];
	}

	/**
	 * Return a slice of entries defined by the parameters $index and $length.
	 * If $search is defined it is used to filter the titles, ignoring case.
	 * Return an array with elements: current page, no. of pages, $length entries
	 * 
	 * @param  string  $class       name of class to return
	 * @param  integer $index=0     page index
	 * @param  integer $length=100  length of page
	 * @param  string  $search=NULL search pattern for sort/name fields
	 * @param  integer $id=NULL     optional author/tag/series ID	 * 
	 * @return array                an array with current page (key 'page'),
	 *                              number of pages (key 'pages'),
	 *                              an array of $class instances (key 'entries') or NULL
	 */
	function findSlice($class, $index=0, $length=100, $search=NULL, $id=NULL) {
		if ($index < 0 || $length < 1 || !in_array($class, array('Book','Author','Tag', 'Series', 'SeriesBook', 'TagBook', 'AuthorBook')))
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
			case 'AuthorBook':
				if (is_null($search)) {
	        $count = 'select count(*) from (select BAL.book, Books.* from books_authors_link BAL, books Books where Books.id=BAL.book and author = '.$id.')';
	        $query = 'select BAL.book, Books.* from books_authors_link BAL, books Books where Books.id=BAL.book and author = '.$id.' order by Books.sort limit '.$length.' offset '.$offset;
	      } else {
	        $count = 'select count(*) from (select BAL.book, Books.* from books_authors_link BAL, books Books where Books.id=BAL.book and author = '.$id.') where lower(sort) like \'%'.strtolower($search).'%\'';
	        $query = 'select BAL.book, Books.* from books_authors_link BAL, books Books where Books.id=BAL.book and author ='.$id.' and lower(Books.sort) like \'%'.strtolower($search).'%\' order by Books.sort limit '.$length.' offset '.$offset;
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
			case 'Series': 
				if (is_null($search)) {
					$count = 'select count(*) from series';
					$query = 'select series.id, series.name, count(bsl.id) as anzahl from series left join books_series_link as bsl on series.id = bsl.series group by series.id order by series.name limit '.$length.' offset '.$offset;
				}	else {
					$count = 'select count(*) from series where lower(name) like \'%'.strtolower($search).'%\'';
					$query = 'select series.id, series.name, count(bsl.id) as anzahl from series left join books_series_link as bsl on series.id = bsl.series where lower(series.name) like \'%'.strtolower($search).'%\' group by series.id order by series.name limit '.$length.' offset '.$offset;	
				}
				break;			
			case 'SeriesBook':
				if (is_null($search)) {
					$count = 'select count (*) from (select BSL.book, Books.* from books_series_link BSL, books Books where Books.id=BSL.book and series = '.$id.')';
					$query = 'select BSL.book, Books.* from books_series_link BSL, books Books where Books.id=BSL.book and series = '.$id.' order by series_index limit '.$length.' offset '.$offset;	          
				} else {
					$count = 'select count (*) from (select BSL.book, Books.* from books_series_link BSL, books Books where Books.id=BSL.book and series = '.$id.') where lower(sort) like \'%'.strtolower($search).'%\'';
					$query = 'select BSL.book, Books.* from books_series_link BSL, books Books where Books.id=BSL.book and series = '.$id.' and lower(Books.sort) like \'%'.strtolower($search).'%\' order by series_index limit '.$length.' offset '.$offset;	
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
			case 'TagBook':
				if (is_null($search)) {
					$count = 'select count (*) from (select BTL.book, Books.* from books_tags_link BTL, books Books where Books.id=BTL.book and tag = '.$id.')';
					$query = 'select BTL.book, Books.* from books_tags_link BTL, books Books where Books.id=BTL.book and tag = '.$id.' order by Books.sort limit '.$length.' offset '.$offset;
				}	else {
					$count = 'select count (*) from (select BTL.book, Books.* from books_tags_link BTL, books Books where Books.id=BTL.book and tag = '.$id.') where lower(sort) like \'%'.strtolower($search).'%\'';
					$query = 'select BTL.book, Books.* from books_tags_link BTL, books Books where Books.id=BTL.book and tag = '.$id.' and lower(Books.sort) like \'%'.strtolower($search).'%\' order by Books.sort limit '.$length.' offset '.$offset;
				}			
				break;	
		}
		$no_entries = $this->count($count);
		$no_pages = (int) ($no_entries / $length);
		if ($no_entries % $length > 0)
			$no_pages += 1;
		$entries = $this->find($class,$query);
		return array('page'=>$index, 'pages'=>$no_pages, 'entries'=>$entries, 'total' => $no_entries);
	}

	/**
	 * Return the number (int) of rows for a SQL COUNT Statement, e.g.
	 * SELECT COUNT(*) FROM books;
	 * 
	 * @param  string 	$sql 	sql query
	 * @return int      			nuber of result rows
	 */
	function count($sql) {
		$result = $this->calibre->query($sql)->fetchColumn(); 
		if ($result == NULL || $result == FALSE)
			return -1;
		else
			return (int) $result;
	}


	/**
	 * Return the most recent books, sorted by modification date.
	 * @param  nrOfTitles	number of titles, page size. Default is 30.
	 * @return array of books
	 */
	function last30Books($nrOfTitles=30) {
		$books = $this->find('Book','select * from books order by timestamp desc limit '.$nrOfTitles);		
		return $books;
	}

	/**
	 * Find a single author and return the details plus all books.
	 * @param  integer $id 	author id
	 * @return array     		array with elements: author data, books
	 */
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
	
	/**
	 * Find a single author and return the details plus some books.
	 * 
	 * @param  integer $id     author id
	 * @param  integer $index  page index
	 * @param  integer $length page length
	 * @return array           array with elements: author data, current page, 
	 *                               no. of pages, $length entries
	 */
	function authorDetailsSlice($id, $index=0, $length=100) {
		$author = $this->findOne('Author', 'select * from authors where id='.$id);
		if (is_null($author)) return NULL;
		$slice = $this->findSlice('AuthorBook', $index, $length, NULL, $id);
		return array('author' => $author)+$slice;
	}

	/**
	 * Search a list of authors defined by the parameters $index and $length.
	 * If $search is defined it is used to filter the names, ignoring case.
	 * 
	 * @param  integer $index  page index
	 * @param  integer $length page length
	 * @param  string  $search search string
	 * @return array 				with elements: current page, 
	 *                      no. of pages, $length entries
	 */
	function authorsSlice($index=0, $length=100, $search=NULL) {
		return $this->findSlice('Author', $index, $length, $search);
	}

	/**
	 * Find the initials of all authors and their count
	 * @return array an array of Items with initial character and author count
	 */
	function authorsInitials() {
		return $this->find('Item', 'select substr(upper(sort),1,1) as initial, count(*) as ctr from authors group by initial order by initial asc');
	}

	/**
	 * Find all authors with a given initial and return their names and book count
	 * @param  string $initial initial character of last name, uppercase
	 * @return array           array of authors with book count
	 */
	function authorsNamesForInitial($initial) {
		return $this->find('Author', 'select a.id, a.name, a.sort, count(bal.id) as anzahl from authors as a left join books_authors_link as bal on a.id = bal.author where substr(upper(a.sort),1,1) = \''.$initial.'\' group by a.id order by a.sort');	
	}

	# Returns a tag and the related books
	#
	
	/**
	 * Returns a tag and the related books
	 * @param  integer $id 	tag id
	 * @return array     		array with elements: tag data, books
	 */
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

	/**
	 * Find a single tag and return the details plus some books.
	 * 
	 * @param  integer $id     tagid
	 * @param  integer $index  page index
	 * @param  integer $length page length
	 * @return array           array with elements: tag data, current page, 
	 *                               no. of pages, $length entries
	 */
	function tagDetailsSlice($id, $index=0, $length=100) {
		$tag = $this->findOne('Tag', 'select * from tags where id='.$id);
		if (is_null($tag)) return NULL;
		$slice = $this->findSlice('TagBook', $index, $length, NULL, $id);
		return array('tag' => $tag)+$slice;
	}

	# Search a list of tags defined by the parameters $index and $length.
	# If $search is defined it is used to filter the tag names, ignoring case.
	# Return an array with elements: current page, no. of pages, $length entries
	function tagsSlice($index=0, $length=100, $search=NULL) {
		return $this->findSlice('Tag', $index, $length, $search);
	}

	/**
	 * Find the initials of all tags and their count
	 * @return array an array of Items with initial character and tag count
	 */
	function tagsInitials() {
		return $this->find('Item', 'select substr(upper(name),1,1) as initial, count(*) as ctr from tags group by initial order by initial asc');
	}

	/**
	 * Find all authors with a given initial and return their names and book count
	 * @param  string $initial initial character of last name, uppercase
	 * @return array           array of authors with book count
	 */
	function tagsNamesForInitial($initial) {
		return $this->find('Tag', 'select tags.id, tags.name, count(btl.id) as anzahl from tags left join books_tags_link as btl on tags.id = btl.tag where substr(upper(tags.name),1,1) = \''.$initial.'\' group by tags.id order by tags.name');	
	}


	# Search a list of books defined by the parameters $index and $length.
	# If $search is defined it is used to filter the book title, ignoring case.
	# Return an array with elements: current page, no. of pages, $length entries
	function titlesSlice($index=0, $length=100, $search=NULL) {
		return $this->findSlice('Book', $index, $length, $search);
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

	/**
	 * Returns the path to a thumbnail of a book's cover image or NULL. 
	 * 
	 * If a thumbnail doesn't exist the function tries to make one from the cover.
	 * The thumbnail dimension generated is 160*160, which is more than what 
	 * jQuery Mobile requires (80*80). However, if we send the 80*80 resolution the 
	 * thumbnails look very pixely.
	 * @param  int 				$id book id
	 * @param  bool  			true = clip the thumbnail, else stuff it
	 * @return string     thumbnail path or NULL
	 */
	function titleThumbnail($id, $clipped) {
		$thumb_name = 'thumb_'.$id.'.png';
		$thumb_path = $this->thumb_dir.'/'.$thumb_name;
		if (!file_exists($thumb_path)) {
			$cover = $this->titleCover($id);
			if (is_null($cover))
				$thumb_path = NULL;
			else {
				if ($clipped)
					$created = $this->titleThumbnailClipped($cover, self::THUMB_RES, self::THUMB_RES, $thumb_path);
				else
					$created = $this->titleThumbnailStuffed($cover, self::THUMB_RES, self::THUMB_RES, $thumb_path);
				if (!$created)
					$thumb_path = NULL;
			}
		}
		return $thumb_path;
	}
	
	/**
	 * Create a square thumbnail by clipping the largest possible square from the cover
	 * @param  string $cover      path to book cover image
	 * @param  int 	 	$newwidth   required thumbnail width
	 * @param  int 		$newheight  required thumbnail height
	 * @param  string $thumb_path path for thumbnail storage
	 * @return bool             	true = thumbnail created
	 */
	function titleThumbnailClipped($cover, $newwidth, $newheight, $thumb_path) {
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
		return $created;
	}

	/**
	 * Create a square thumbnail by stuffing the cover at the edges
	 * @param  string $cover      path to book cover image
	 * @param  int 	 	$newwidth   required thumbnail width
	 * @param  int 		$newheight  required thumbnail height
	 * @param  string $thumb_path path for thumbnail storage
	 * @return bool             	true = thumbnail created
	 */
	function titleThumbnailStuffed($cover, $newwidth, $newheight, $thumb_path) {
		list($width, $height) = getimagesize($cover);
		$thumb = Utilities::transparentImage($newwidth, $newheight);
		$source = imagecreatefromjpeg($cover);
		$dstx = 0;
		$dsty = 0;
		$maxwh = max(array($width, $height));
		if ($height > $width) {
			$diff = $maxwh - $width;
			$dstx = (int) $diff/2;
		} else {
			$diff = $maxwh - $height;
			$dsty = (int) $diff/2;
		}
		$inbetween = Utilities::transparentImage($maxwh, $maxwh);
		imagecopy($inbetween, $source, $dstx, $dsty, 0, 0, $width, $height);				
		imagecopyresampled($thumb, $inbetween, 0, 0, 0, 0, $newwidth, $newheight, $maxwh, $maxwh);
		$created = imagepng($thumb, $thumb_path);				
		imagedestroy($thumb);
		imagedestroy($inbetween);
		imagedestroy($source);
		return $created;
	}

	/**
	 * Delete existing thumbnail files
	 * @return bool false if there was an error
	 */
	function clearThumbnails() {
		$cleared = true;
		if($dh = opendir($this->thumb_dir)){
	    while(($file = readdir($dh)) !== false) {
	    	$fn = $this->thumb_dir.'/'.$file;
	      if(fnmatch("thumb*.png", $file) && file_exists($fn)) {
	      	if (!@unlink($fn)) {
	      		$cleared = false;
	      		break;
	      	}
	      }
	    }
			closedir($dh);
		} else 
			$cleared = false;
		return $cleared;
	}

	/**
	* Try to find the language of a book. Returns an emty string, if there is none.
	* @param  int 		$book_id 	the Calibre book ID
	* @return string 				the language string or an empty string
	**/
	function getLanguage($book_id) {
		$lang_code = null;
		$lang_id = $this->findOne('BookLanguageLink', 'select * from books_languages_link where book='.$book_id);
		if (!is_null($lang_id))
			$lang_code = $this->findOne('Language', 'select * from languages where id='.$lang_id->lang_code);
		if (is_null($lang_code))
			$lang_text = '';
		else
			$lang_text = $lang_code->lang_code;
		return $lang_text;		
	}

	/**
	 * Find a single book, its authors, series, tags, formats and comment.
	 * @param  int 		$id 	the Calibre book ID
	 * @return array     		the book and its authors, series, tags, formats, and comment/description
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
		$series_ids = $this->find('BookSeriesLink', 'select * from books_series_link where book='.$id);
		$series = array();
		foreach($series_ids as $aid) {
			$this_series = $this->findOne('Series', 'select * from series where id='.$aid->series);
			array_push($series, $this_series);
		}		
		$tag_ids = $this->find('BookTagLink', 'select * from books_tags_link where book='.$id);
		$tags = array();
		foreach($tag_ids as $tid) {
			$tag = $this->findOne('Tag', 'select * from tags where id='.$tid->tag);
			array_push($tags, $tag);
		}
		$lang_text = $this->getLanguage($id);
		$formats = $this->find('Data', 'select * from data where book='.$id);
		$comment = $this->findOne('Comment', 'select * from comments where book='.$id);
		if (is_null($comment))
			$comment_text = '';
		else
			$comment_text = $comment->text;		
		$customColumns = $this->customColumns($id);
		return array('book' => $book, 
			'authors' => $authors, 
			'series' => $series, 
			'tags' => $tags, 
			'formats' => $formats, 
			'comment' => $comment_text, 
			'language' => $lang_text,
			'custom' => $customColumns);
	}

	# Add a new cc value. If the key already exists, combine the values with a string join.
	function addCc($def, $value, $result) {
		if (array_key_exists($def->name, $result)) {
			$oldv = $result[$def->name];
			$oldv['value'] = $oldv['value'].', '.$value;
			$result[$def->name] = $oldv;
		} else
			$result[$def->name] = array('name'=>$def->name,'type'=>$def->datatype, 'value'=>$value);
		return $result;
	}

	/**
	* Find the custom colums for a book.
	* Composite columns are ignored, because there are (currently?) no values in 
	* the db tables.
	* @param  integer 	$book_id 	ID of the book
	* @return array 				an array of arrays. one entry for each custom column
	*								with name, type and value
	*/
	function customColumns($book_id){
		$columns = $this->find('CustomColumns', 'select * from custom_columns');
		$ccs = array();
		foreach ($columns as $column) {
			$column_id = $column->id;
			if ($column->datatype == 'composite' || $column->datatype == 'series') {
				# composites have no data in the tables; they are template expressions
				# that are apparently evalued dynamically, so we ignore them
				# series contain two data values -- one in the link table, one in the cc table -- handling?
				continue;
			} else if ($column->datatype == 'text' || $column->datatype == 'enumeration' || $column->datatype == 'rating') {
				# these have extra link tables
				$lvs = $this->find('BooksCustomColumnLink', 'select * from books_custom_column_'.$column_id.'_link where book='.$book_id);
				foreach ($lvs as $lv) {
					$cvs = $this->find('CustomColumn', 'select * from custom_column_'.$column_id.' where id='.$lv->value);
					foreach ($cvs as $cv) {
						$ccs = $this->addCc($column, $cv->value, $ccs);
					}
				}
			} else {
				# these need just the cc table
				$cvs = $this->find('CustomColumn', 'select * from custom_column_'.$column_id.' where book='.$book_id);
				foreach ($cvs as $cv) {
					$ccs = $this->addCc($column, $cv->value, $ccs);
				}
			}
		}

		return $ccs;
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
		$lang_id = $this->findOne('BookLanguageLink', 'select * from books_languages_link where book='.$book->id);
		if (is_null($lang_id))
			$lang_text = '';
		else {
			$lang_code = $this->findOne('Language', 'select * from languages where id='.$lang_id->lang_code);
			if (is_null($lang_code))
				$lang_text = '';
			else
				$lang_text = $lang_code->lang_code;			
		}
		$comment = $this->findOne('Comment', 'select * from comments where book='.$book->id);
		if (is_null($comment))
			$comment_text = '';
		else
			$comment_text = $comment->text;
			# Strip html excluding the most basic tags and remove all tag attributes
			$comment_text = strip_tags($comment_text, '<div><strong><i><em><b><p><br><br/>');
			$comment_text = preg_replace("/<([a-z][a-z0-9]*)[^>]*?(\/?)>/i",'<$1$2>', $comment_text);
		$formats = $this->find('Data', 'select * from data where book='.$book->id);
		return array('book' => $book, 'authors' => $authors, 'tags' => $tags, 
			'formats' => $formats, 'comment' => $comment_text, 'language' => $lang_text);
	}

	/**
	 * Retrieve the OPDS title details for a collection of Books and
	 * filter out the titles without a downloadable format.
	 *
	 * This is a utilty function for OPDS, because OPDS acquisition feeds don't 
	 * valdate if there are entries without acquisition links to downloadable files.
	 * 
	 * @param  array 	$books a collection of Book instances
	 * @return array         the book and its authors, tags and formats
	 */
	function titleDetailsFilteredOpds($books) {
		$filtered_books = array();
		foreach ($books as $book) {
			$record = $this->titleDetailsOpds($book);
			if (!empty($record['formats']))
				array_push($filtered_books,$record);
		}
		return $filtered_books;
	}

	/**
	 * Returns the path to the file of a book or NULL.
	 * @param  int 		$id   book id
	 * @param  string $file file name
	 * @return string       full path to image file or NULL
	 */
	function titleFile($id, $file) {
		$book = $this->title($id);
		if (is_null($book)) 
			return NULL;
		else 
			return Utilities::bookPath($this->calibre_dir,$book->path,$file);
	}

	/**
	 * Return the formats for a book 
	 * @param  int 		$bookid Calibre book id
	 * @return array  				the formats for the book
	 */
	function titleGetFormats($bookid) {
		return $this->find('Data', 'select * from data where book='.$bookid);
	}

	/**
	 * Returns a Kindle supported format of a book or NULL.
	 * We always return the best of the available formats supported by Kindle devices
	 * E.g. when there is both a Mobi and a PDF file for a given book, we always return the Mobi
	 * @param  int 		$id   		book id	
	 * @return object   $format 	the kindle format object for the book or NULL
	 */
	function titleGetKindleFormat($id) {
		$book = $this->title($id);
		if (is_null($book)) return NULL;
		$formats = $this->find("Data", "select * from data where book=".$id." AND (format='AZW' OR format='AZW3' OR format='MOBI' OR format='HTML' OR format='PDF')");
		if(empty($formats))
			return NULL;
		else {
			usort($formats, array($this, 'kindleFormatSort'));
			$format=$formats[0];
		}
		return $format;
	}

	/**
	 * Find a single series and return the details plus all books.
	 * @param  int 		$id series id
	 * @return array  an array with series details (key 'series') and 
	 *                the related books (key 'books')
	 * @deprecated since 0.9.3
	 */
	function seriesDetails($id) {
		$series = $this->findOne('Series', 'select * from series where id='.$id);
		if (is_null($series)) return NULL;
		$books = $this->find('Book', 'select BSL.book, Books.* from books_series_link BSL, books Books where Books.id=BSL.book and series='.$id.' order by series_index');
		return array('series' => $series, 'books' => $books);
	}

	/**
	 * Find a single series and return the details plus some books.
	 * 
	 * @param  integer $id     series id
	 * @param  integer $index  page index
	 * @param  integer $length page length
	 * @return array           array with elements: series data, current page, 
	 *                               no. of pages, $length entries
	 */
	function seriesDetailsSlice($id, $index=0, $length=100) {
		$series = $this->findOne('Series', 'select * from series where id='.$id);
		if (is_null($series)) return NULL;
		$slice = $this->findSlice('SeriesBook', $index, $length, NULL, $id);
		return array('series' => $series)+$slice;
	}

	/**
	 * Search a list of books defined by the parameters $index and $length.
	 * If $search is defined it is used to filter the book title, ignoring case.
	 * Return an array with elements: current page, no. of pages, $length entries
	 * 
	 * @param  integer $index=0     page indes
	 * @param  integer $length=100  page length
	 * @param  string  $search=NULL search criteria for series name
	 * @return array                see findSlice
	 */
	function seriesSlice($index=0, $length=100, $search=NULL) {
		return $this->findSlice('Series', $index, $length, $search);
	}

	/**
	 * Find the initials of all series and their number
	 * @return array an array of Items with initial character and series count
	 */
	function seriesInitials() {
		return $this->find('Item', 'select substr(upper(name),1,1) as initial, count(*) as ctr from series group by initial order by initial asc');
	}

	/**
	 * Find all series with a given initial and return their names and book count
	 * @param  string $initial initial character of name, uppercase
	 * @return array           array of Series with book count
	 */
	function seriesNamesForInitial($initial) {
		return $this->find('Series', 'select series.id, series.name, count(btl.id) as anzahl from series left join books_series_link as btl on series.id = btl.series where substr(upper(series.name),1,1) = \''.$initial.'\' group by series.id order by series.name');	
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

	/**
	 * Usort helper function
	 * sorts the formats array-of-objects by priority set by kindleformats array
	 */
	function kindleFormatSort($a, $b) 
	{ 
	  //global $kindleformats;
	  $kindleformats[0] = "AZW3"; 
	  $kindleformats[1] = "AZW"; 
	  $kindleformats[3] = "MOBI"; 
	  $kindleformats[4] = "HTML";
	  $kindleformats[5] = "PDF";

	  foreach($kindleformats as $key => $value) 
	    { 
	      if($a->format == $value) 
	        { 
	          return 0; 
	          break; 
	        } 

	      if($b->format == $value) 
	        { 
	          return 1; 
	          break; 
	        } 
	    } 
	} 

}
?>
