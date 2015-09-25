<?php
/**
 * Utility items
 */

# A database item class
class Item {}

# Utiliy classes for Calibre DB items
class Author extends Item {}
class AuthorBook extends Item {}
class Book extends Item {}
class BookAuthorLink extends Item {}
class BooksCustomColumnLink extends Item {}
class BookSeriesLink extends Item {}
class BookTagLink extends Item {}
class BookLanguageLink extends Item {}
class Comment extends Item {}
class CustomColumn extends Item {}
class CustomColumns extends Item {}
class Data extends Item {}
class Language extends Item {}
class Series extends Item {}
class SeriesBook extends Item {}
class Tag extends Item {}
class TagBook extends Item {}
class Identifier extends Item {}


# Search types for Calibre::findSliceFiltered
abstract class CalibreSearchType
{
    const Author = 1;
    const AuthorBook = 2;
    const Book = 3;
    const Series = 4;
    const SeriesBook = 5;
    const Tag = 6;
    const TagBook = 7;
    const TimestampOrderedBook = 8;
    const PubDateOrderedBook = 9;
    const LastModifiedOrderedBook = 10;
}

# Configuration utilities for BBS
class Encryption extends Item{}
class ConfigMailer extends Item{}

class ConfigTtsOption extends Item
{
}
class IdUrlTemplate extends Item{}

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

    const MIME_EPUB = 'application/epub+zip';

    /**
     * Check if a string starts with a substring.
     * 
     * Works around a strange feature of Calibre where middle components of names are capitalized, 
     * eg "Aliette de Bodard" -> "Aliette De Bodard".
     * The directory name uses the capitalized form, the book path stored in the DB uses the original 
     * form.
     * @param  string $haystack String to be searched
     * @param  string $needle   String to search for
     * @return boolean          true if $haystack starts with $needle, case insensitive
     */
    static function stringStartsWith($haystack, $needle) {
        return (stripos($haystack, $needle) === 0);
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
            return Utilities::MIME_EPUB;
        else if (preg_match('/mobi$/', $file_path) == 1) 
            return 'application/x-mobipocket-ebook';
        else if (preg_match('/azw3?$/', $file_path) == 1) 
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
        } else if (function_exists('finfo_file')){
                 $finfo = finfo_open(FILEINFO_MIME);
                 $mtype = finfo_file($finfo, $file_path);
                 finfo_close($finfo);  
        }
        
        if ($mtype == ''){
                 $mtype = 'application/force-download';
        }
        
        return $mtype;
    }

    /**
     * Create an image with transparent background. 
     *
     * see http://stackoverflow.com/questions/279236/how-do-i-resize-pngs-with-transparency-in-php#279310
     * 
     * @param  int  $width  
     * @param  int  $height 
     * @return image        
     */
    static function transparentImage($width, $height) {
        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, false);
        imagesavealpha($img, true);                 
        $backgr = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefilledrectangle($img, 0, 0, $width, $height, $backgr);
        return $img;
    }

}


?>