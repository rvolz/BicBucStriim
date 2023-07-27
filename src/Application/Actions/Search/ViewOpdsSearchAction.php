<?php

namespace App\Application\Actions\Search;

use App\Application\Actions\CalibreOpdsAction;
use App\Domain\BicBucStriim\AppConstants;
use App\Domain\Opds\OpdsGenerator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ViewOpdsSearchAction extends CalibreOpdsAction
{
    /**
     * Create and send the catalog page for the current search criteria.
     * @throws HttpBadRequestException
     */
    protected function action(): Response
    {
        $search = '';
        if ($this->hasQueryParam('search')) {
            $search = $this->resolveQueryParam('search');
        }
        $index = 0;
        if ($this->hasQueryParam('index')) {
            $index = (int) $this->resolveQueryParam('index');
        }

        if ($search == '') {
            $this->logger->error('OpdsSearch called without search criteria, page ' . $index);
            throw new HttpBadRequestException($this->request);
        }
        $filter = $this->getFilter();
        $lang = $this->l10n->user_lang;
        $pg_size = $this->config[AppConstants::PAGE_SIZE];
        $tl = $this->calibre->titlesSlice($lang, $index, $pg_size, $filter, $search);
        $books1 = $this->calibre->titleDetailsFilteredOpds($tl['entries']);
        $books = array_map([$this,'checkThumbnailOpds'], $books1);
        $cat = $this->gen->searchCatalog(
            null,
            $books,
            false,
            $tl['page'],
            $this->getNextSearchPage($tl),
            $this->getLastSearchPage($tl),
            $search,
            $tl['total'],
            $pg_size
        );
        return $this->respondWithOpds($cat, OpdsGenerator::OPDS_MIME_ACQ);
    }
}
