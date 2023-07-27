<?php

namespace App\Application\Actions\Titles;

use App\Domain\BicBucStriim\AppConstants;
use App\Domain\Opds\OpdsGenerator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ViewOpdsTitlesAction extends \App\Application\Actions\CalibreOpdsAction
{
    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $index = 0;
        if ($this->hasQueryParam('index')) {
            $index = (int) $this->resolveQueryParam('index');
        }
        // parameter checking
        if ($index < 0) {
            $this->logger->warning('ViewOpdsTitlesAction: invalid page id ' . $index);
            throw new HttpBadRequestException($this->request);
        }

        $search = '';
        if ($this->hasQueryParam('search')) {
            $search = $this->resolveQueryParam('search');
        }

        $filter = $this->getFilter();
        $lang = $this->l10n->user_lang;
        $pg_size = $this->config[AppConstants::PAGE_SIZE];
        if (!empty($search)) {
            $tl = $this->calibre->titlesSlice($lang, $index, $pg_size, $filter, $search);
        } else {
            $tl = $this->calibre->titlesSlice($lang, $index, $pg_size, $filter);
        }
        $books1 = $this->calibre->titleDetailsFilteredOpds($tl['entries']);
        $books = array_map([$this, 'checkThumbnailOpds'], $books1);
        $cat = $this->gen->titlesCatalog(
            null,
            $books,
            false,
            $tl['page'],
            $this->getNextSearchPage($tl),
            $this->getLastSearchPage($tl)
        );
        return $this->respondWithOpds($cat, OpdsGenerator::OPDS_MIME_ACQ);
    }
}
