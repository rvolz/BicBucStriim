<?php
/**
 * Utility items
 */

# A database item class
class Item {}

# Utiliy classes for Calibre DB items
class Book extends Item {}
class Author extends Item {}
class BookAuthorLink extends Item {}
class BookTagLink extends Item {}
class Tag extends Item {}
class Data extends Item {}
class Comment extends Item {}

# Confiuration items in the BBS DB
class Config extends Item{}

class Utilities {
	/**
	 * Return the true path of a book. 
	 * 
	 * Works around a strange feature of Calibre where middle components of names are capitalized, 
	 * eg "Aliette de Bodard" -> "Aliette De Bodard".
	 * The directory name uses the capitalized form, the book path stored in the DB uses the original 
	 * form.
	 * @param  string $cd   Calibre library directory
	 * @param  string $bp   book path, as stored in the DB
	 * @param  string $file file name
	 * @return string       the filesystem path to the book file
	 */
	static function bookPath($cd, $bp, $file) {
		try {
			$path = $cd.'/'.$bp.'/'.$file;
			stat($path);
		} catch (Exception $e) {
			$p = explode("/",$bp);
			$path = $cd.'/'.ucwords($p[0]).'/'.$p[1].'/'.$file;
		}
		return $path;
	}

	static function filterEmptyBooks($books) {

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
	static function titleMimeType($file_path) {
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
}


?>