<?php


namespace App\Application\Actions\Start;


use App\Domain\BicBucStriim\AppConstants;
use App\Domain\Opds\OpdsGenerator;
use Psr\Http\Message\ResponseInterface as Response;

class ViewOpdsNewestAction extends \App\Application\Actions\CalibreOpdsAction
{

    /**
     * Generate and send the OPDS 'newest' catalog. This catalog is an
     * acquisition catalog with a subset of the title details.
     *
     * Note: OPDS acquisition feeds need an acquisition link for every item,
     * so books without formats are removed from the output.
     *
     * @return Response
     */
    protected function action(): Response
    {
        $filter = $this->getFilter();
        $just_books = $this->calibre->last30Books($this->l10n->user_lang, $this->config[AppConstants::PAGE_SIZE], $filter);
        $books1 = array();
        foreach ($just_books as $book) {
            $record = $this->calibre->titleDetailsOpds($book);
            if (!empty($record['formats']))
                array_push($books1, $record);
        }
        $books = array_map(array($this, 'checkThumbnailOpds'), $books1);
        $cat = $this->gen->newestCatalog(null, $books, false);
        return $this->respondWithOpds($cat, OpdsGenerator::OPDS_MIME_ACQ);
    }
}