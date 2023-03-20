<?php

namespace App\Application\Actions\Authors;

use App\Domain\BicBucStriim\AppConstants;
use App\Domain\DomainException\DomainRecordNotFoundException;
use App\Domain\Opds\OpdsGenerator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ViewOpdsByAuthorAction extends \App\Application\Actions\CalibreOpdsAction
{
    /**
     * Return a feed with partial acquisition entries for an author's books
     * @param  string    initial initial character
     * @param  int        id      author id
     * @param  int        page    page number
     * @return Response
     */
    protected function action(): Response
    {
        $initial = $this->resolveArg('initial');
        $id = (int) $this->resolveArg('id');
        $index = 0;
        if ($this->hasQueryParam('page')) {
            $index = (int) $this->resolveQueryParam('page');
        }
        // parameter checking
        if ($index < 0) {
            $this->logger->warning('ViewOpdsByAuthorAction: invalid page id ' . $index);
            throw new HttpBadRequestException($this->request);
        }
        $filter = $this->getFilter();
        $tl = $this->calibre->authorDetailsSlice(
            $this->l10n->user_lang,
            $id,
            $index,
            $this->config[AppConstants::PAGE_SIZE],
            $filter
        );
        if (empty($tl)) {
            $msg = sprintf("ViewOpdsByAuthorAction: no title data found for id %d", $id);
            $this->logger->error($msg);
            throw new DomainRecordNotFoundException($msg);
        }
        $books1 = $this->calibre->titleDetailsFilteredOpds($tl['entries']);
        $books = array_map([$this, 'checkThumbnailOpds'], $books1);
        $cat = $this->gen->booksForAuthorCatalog(
            null,
            $books,
            $initial,
            $tl['author'],
            false,
            $tl['page'],
            getNextSearchPage($tl),
            getLastSearchPage($tl)
        );
        return $this->respondWithOpds($cat, OpdsGenerator::OPDS_MIME_ACQ);
    }
}
