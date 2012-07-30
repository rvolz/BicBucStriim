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
}


?>