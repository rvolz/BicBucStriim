<?php
/**
 * Utility items
 */

# A database item class
class Item
{
}

# Utiliy classes for Calibre DB items
class Author extends Item
{
    public $sort;
    public $name;
}
class AuthorBook extends Item
{
}
class Book extends Item
{
    public $id;
}
class BookAuthorLink extends Item
{
}
class BooksCustomColumnLink extends Item
{
}
class BookSeriesLink extends Item
{
}
class BookTagLink extends Item
{
}
class BookLanguageLink extends Item
{
}
class Comment extends Item
{
}
class CustomColumn extends Item
{
}
class CustomColumns extends Item
{
}
class Data extends Item
{
}
class Language extends Item
{
    public $lang_code;
    public $key;
}
class Series extends Item
{
}
class SeriesBook extends Item
{
}
class Tag extends Item
{
    public $name;
    public $key;
}
class TagBook extends Item
{
}
class Identifier extends Item
{
}


# Search types for Calibre::findSliceFiltered
abstract class CalibreSearchType
{
    public const Author = 1;
    public const AuthorBook = 2;
    public const Book = 3;
    public const Series = 4;
    public const SeriesBook = 5;
    public const Tag = 6;
    public const TagBook = 7;
    public const TimestampOrderedBook = 8;
    public const PubDateOrderedBook = 9;
    public const LastModifiedOrderedBook = 10;
}

# Configuration utilities for BBS
class Encryption extends Item
{
    public $key;
    public $text;
}
class ConfigMailer extends Item
{
    public $key;
    public $text;
}
class ConfigTtsOption extends Item
{
    public $key;
    public $text;
}
class IdUrlTemplate extends Item
{
    public $name;
    public $val;
    public $label;
}

/**
 * Class UrlInfo contains information on how to construct URLs
 */
class UrlInfo
{
    /**
     * @var string $protocol - protocol used for access, default 'http'
     */
    public $protocol = 'http';
    /**
     * @var string $host - hostname or ip address used for access
     */
    public $host;

    public function __construct()
    {
        $na = func_num_args();
        if ($na == 2) {
            $fhost = func_get_arg(0);
            if (!is_null($fhost) && $fhost != 'unknown') {
                $this->host = $fhost;
            }
            $fproto = func_get_arg(1);
            if (!is_null($fproto)) {
                $this->protocol = $fproto;
            }
        } else {
            $ffw = func_get_arg(0);
            $ffws = preg_split('/;/', $ffw, -1, PREG_SPLIT_NO_EMPTY);
            $opts = [];
            foreach ($ffws as $ffwi) {
                $ffwis = preg_split('/=/', $ffwi, -1);
                $opts[$ffwis[0]] = $ffwis[1];
            }
            if (isset($opts['by'])) {
                $this->host = $opts['by'];
            }
            if (isset($opts['proto'])) {
                $this->protocol = $opts['proto'];
            }
        }
    }

    public function __toString()
    {
        return "UrlInfo{ protocol: $this->protocol, host: $this->host}";
    }

    public function is_valid()
    {
        return (!empty($this->host));
    }
}

class Utilities
{
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
    public static function bookPath($cd, $bp, $file)
    {
        try {
            $path = $cd.'/'.$bp.'/'.$file;
            stat($path);
        } catch (Exception $e) {
            $p = explode("/", $bp);
            $path = $cd.'/'.ucwords($p[0]).'/'.$p[1].'/'.$file;
        }
        return $path;
    }

    public const MIME_EPUB = 'application/epub+zip';

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
    public static function stringStartsWith($haystack, $needle)
    {
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
    public static function titleMimeType($file_path)
    {
        $mtype = '';

        if (preg_match('/epub$/', $file_path) == 1) {
            return Utilities::MIME_EPUB;
        } elseif (preg_match('/(mobi|azw)$/', $file_path) == 1) {
            return 'application/x-mobipocket-ebook';
        } elseif (preg_match('/azw(1|2)$/', $file_path) == 1) {
            return 'application/vnd.amazon.ebook';
        } elseif (preg_match('/azw3$/', $file_path) == 1) {
            return 'application/x-mobi8-ebook';
        } elseif (preg_match('/pdf$/', $file_path) == 1) {
            return 'application/pdf';
        } elseif (preg_match('/txt$/', $file_path) == 1) {
            return 'text/plain';
        } elseif (preg_match('/html$/', $file_path) == 1) {
            return 'text/html';
        } elseif (preg_match('/zip$/', $file_path) == 1) {
            return 'application/zip';
        }

        if (function_exists('mime_content_type')) {
            $mtype = mime_content_type($file_path);
        } elseif (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mtype = finfo_file($finfo, $file_path);
            finfo_close($finfo);
        }

        if ($mtype == '') {
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
     * @return object image
     */
    public static function transparentImage($width, $height)
    {
        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $backgr = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefilledrectangle($img, 0, 0, $width, $height, $backgr);
        return $img;
    }
}
