<?php


namespace App\Application\Actions\Search;


use App\Domain\BicBucStriim\AppConstants;
use App\Domain\Calibre\SearchOptions;
use Psr\Http\Message\ResponseInterface as Response;

class UpdateSearchAction extends SearchAction
{

    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $search_params = $this->request->getParsedBody();
        $this->logger->debug("Search params received", [__FILE__, var_export($search_params, true)]);
        $search = '';
        if (array_key_exists('search', $search_params))
            $search = $search_params['search'];
        $respect_case = false;
        if (array_key_exists('case', $search_params))
            $respect_case = true;
        $ascii_transliteration = false;
        if (array_key_exists('transliteration', $search_params))
            $ascii_transliteration = true;
        $searchOptions = empty($search) ? null : new SearchOptions($search, $respect_case, $ascii_transliteration);
        $this->logger->debug('search params ', [var_export($searchOptions, true)]);

        $filter = $this->getFilter();
        $lang = $this->l10n->user_lang;
        $pg_size = 3; //$this->config[AppConstants::PAGE_SIZE];
        $tlb = $this->calibre->titlesSlice($lang, 0, $pg_size, $filter, $searchOptions);
        $tlb_books = array_map(array($this, 'checkThumbnail'), $tlb['entries']);
        $tla = $this->calibre->authorsSlice(0, $pg_size, $searchOptions);
        $tla_books = array_map(array($this, 'checkAuthorThumbnail'), $tla['entries']);
        $tlt = $this->calibre->tagsSlice(0, $pg_size, $searchOptions);
        //$tlt_books = array_map(array($this, 'checkThumbnail'), $tlt['entries']);
        $tls = $this->calibre->seriesSlice(0, $pg_size, $searchOptions);
        //$tls_books = array_map(array($this, 'checkThumbnail'), $tls['entries']);

        $this->logger->info('found books',[__FILE__, $tlb['total']]);
        return $this->respondWithPage('global_search_result.twig', array(
            'page' => $this->mkPage($this->getMessageString('pagination_search'), 0),
            'conf_transliteration' => $this->config[AppConstants::SEARCH_ASCII_TRANSLITERATION],
            'books' => $tlb_books,
            'books_total' => $tlb['total'] == -1 ? 0 : $tlb['total'],
            'more_books' => ($tlb['total'] > $pg_size),
            'authors' => $tla_books,
            'authors_total' => $tla['total'] == -1 ? 0 : $tla['total'],
            'more_authors' => ($tla['total'] > $pg_size),
            'tags' => $tlt,
            'tags_total' => $tlt['total'] == -1 ? 0 : $tlt['total'],
            'more_tags' => ($tlt['total'] > $pg_size),
            'series' => $tls,
            'series_total' => $tls['total'] == -1 ? 0 : $tls['total'],
            'more_series' => ($tls['total'] > $pg_size),
            'search' => $search,
            'case' => $respect_case,
            'transliteration' => $ascii_transliteration));
    }
}