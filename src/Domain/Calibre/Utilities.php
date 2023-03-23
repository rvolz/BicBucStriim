<?php
/**
 * Utility items
 */

namespace App\Domain\Calibre;

use Exception;

class Utilities
{
    /**
     * Return the true path of a book.
     *
     * Works around a strange feature of Calibre where middle components of names are capitalized,
     * eg "Aliette de Bodard" -> "Aliette De Bodard".
     * The directory name uses the capitalized form, the book path stored in the DB uses the original
     * form.
     * @param string $cd Calibre library directory
     * @param string $bp book path, as stored in the DB
     * @param string $file file name
     * @return string       the filesystem path to the book file
     */
    public static function bookPath(string $cd, string $bp, string $file): ?string
    {
        try {
            $path = $cd . '/' . $bp . '/' . $file;
            if (!stat($path)) {
                $p = explode("/", $bp);
                $path = $cd . '/' . ucwords($p[0]) . '/' . $p[1] . '/' . $file;
                if (!stat($path)) {
                    return null;
                }
            }
        } catch (Exception $e) {
            return null;
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
     * @param  string $needle String to search for
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
        } elseif (preg_match('/mobi$/', $file_path) == 1) {
            return 'application/x-mobipocket-ebook';
        } elseif (preg_match('/azw3?$/', $file_path) == 1) {
            return 'application/vnd.amazon.ebook';
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
     * @param  int $width
     * @param  int $height
     * @return object|resource image
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
