<?php


namespace App\Application\Actions\Titles;


use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

abstract class TitlesAction extends \App\Application\Actions\CalibreHtmlAction
{

    /**
     * Creates a human readable filesize string
     * @param string $bytes
     * @param int $decimals
     * @return string
     */
    protected function human_filesize(string $bytes, $decimals = 0): string
    {
        $size = array('B','KB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }

    /**
     * Checks if a title is available to the current users
     * @param array $book_details output of BicBucStriim::title_details()
     * @return bool true if the title is not availble for the user, else false
     */
    protected function title_forbidden(array $book_details): bool
    {
        $user = $this->user;
        $ulang = $user->getLanguages();
        $utag = $user->getTags();
        if (empty($ulang) && empty($utag)) {
            return false;
        } else {
            if (!empty($ulang)) {
                $lang_found = false;
                foreach ($book_details['langcodes'] as $langcode) {
                    if ($langcode === $ulang) {
                        $lang_found = true;
                        break;
                    }
                }
                if (!$lang_found) {
                    return true;
                }
            }
            if (!empty($utag)) {
                $tag_found = false;
                foreach ($book_details['tags'] as $tag) {
                    if ($tag->name === $utag) {
                        $tag_found = true;
                        break;
                    }
                }
                if ($tag_found) {
                    return true;
                }
            }
            return false;
        }
    }
}