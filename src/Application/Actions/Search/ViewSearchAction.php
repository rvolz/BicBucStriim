<?php

namespace App\Application\Actions\Search;

use App\Domain\BicBucStriim\AppConstants;
use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ViewSearchAction extends SearchAction
{
    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $search = '';
        if ($this->hasQueryParam('search')) {
            $search = $this->resolveQueryParam('search');
        }
        $filter = $this->getFilter();
        $lang = $this->l10n->user_lang;
        $pg_size = $this->config[AppConstants::PAGE_SIZE];
        $tlb = $this->calibre->titlesSlice($lang, 0, $pg_size, $filter, trim($search));
        $tlb_books = array_map([$this,'checkThumbnail'], $tlb['entries']);
        $tla = $this->calibre->authorsSlice(0, $pg_size, trim($search));
        $tla_books = array_map([$this,'checkThumbnail'], $tla['entries']);
        $tlt = $this->calibre->tagsSlice(0, $pg_size, trim($search));
        $tlt_books = array_map([$this,'checkThumbnail'], $tlt['entries']);
        $tls = $this->calibre->seriesSlice(0, $pg_size, trim($search));
        $tls_books = array_map([$this,'checkThumbnail'], $tls['entries']);
        return $this->respondWithPage('global_search.html', [
            'page' => $this->mkPage($this->getMessageString('pagination_search'), 0),
            'books' => $tlb_books,
            'books_total' => $tlb['total'] == -1 ? 0 : $tlb['total'],
            'more_books' => ($tlb['total'] > $pg_size),
            'authors' => $tla_books,
            'authors_total' => $tla['total'] == -1 ? 0 : $tla['total'],
            'more_authors' => ($tla['total'] > $pg_size),
            'tags' => $tlt_books,
            'tags_total' => $tlt['total'] == -1 ? 0 : $tlt['total'],
            'more_tags' => ($tlt['total'] > $pg_size),
            'series' => $tls_books,
            'series_total' => $tls['total'] == -1 ? 0 : $tls['total'],
            'more_series' => ($tls['total'] > $pg_size),
            'search' => $search]);
    }
}
