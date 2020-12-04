<?php

use App\Domain\BicBucStriim\AppConstants;
use App\Domain\Opds\OpdsGenerator;
use App\Domain\Calibre\Utilities;
use Slim\Psr7\Factory\StreamFactory;

$app->group('/opds', function () {
    /**
     * Generate and send the OPDS root navigation catalog
     */
    $this->get('/', function ($request, $response, $args) {
        $this->logger->debug('/opds ');
        $gen = mkOpdsGenerator($this, $request);
        $cat = $gen->rootCatalog();
        return mkOpdsResponse($response, $cat, OpdsGenerator::OPDS_MIME_NAV);
    });
    /**
     * Format and send the OpenSearch descriptor document
     */
    $this->get('/opensearch.xml', function ($request, $response, $args) {
        $gen = mkOpdsGenerator($this, $request);
        $cat = $gen->searchDescriptor(null, '/opds/searchlist/0/');
        return mkOpdsResponse($response, $cat, OpdsGenerator::OPENSEARCH_MIME);
    });
    /**
     * Generate and send the OPDS 'newest' catalog. This catalog is an
     * acquisition catalog with a subset of the title details.
     *
     * Note: OPDS acquisition feeds need an acquisition link for every item,
     * so books without formats are removed from the output.
     */
    $this->get('/newest/', function ($request, $response, $args) {
        $lang = $this->l10n->user_lang || 'en';
        $psize = $this->config[AppConstants::PAGE_SIZE];
        $filter = getFilter($this);
        $just_books = $this->calibre->last30Books($lang, $psize, $filter);
        $books1 = array();
        foreach ($just_books as $book) {
            $record = $this->calibre->titleDetailsOpds($book);
            if (!empty($record['formats']))
                array_push($books1, $record);
        }
        $books = checkThumbnailOpds($books1, $this->bbs, $this->calibre, $this->config[AppConstants::THUMB_GEN_CLIPPED]);
        $gen = mkOpdsGenerator($this, $request);
        $cat = $gen->newestCatalog(null, $books, false);
        return mkOpdsResponse($response, $cat, OpdsGenerator::OPDS_MIME_ACQ);
    });
    /**
     * Return a page of the titles.
     *
     * Note: OPDS acquisition feeds need an acquisition link for every item,
     * so books without formats are removed from the output.
     *
     * @param  integer $index =0 page index
     */
    $this->get('/titleslist/{id}/', function (Psr\Http\Message\ServerRequestInterface $request, $response, $args) {
        $index = $args['id'];
        // parameter checking
        if (!is_numeric($index)) {
            $this->logger->warn('opdsByTitle: invalid page id ' . $index);
            return $response->withStatus(400)->write('Bad parameter');
        }
        $params = $request->getQueryParams();
        $lang = $this->l10n->user_lang || 'en';
        $psize = $this->config[AppConstants::PAGE_SIZE];
        $filter = getFilter($this);
        if (isset($params['search']))
            $tl = $this->calibre->titlesSlice($lang, $index, $psize, $filter, $params['search']);
        else
            $tl = $this->calibre->titlesSlice($lang, $index, $psize, $filter);
        $books1 = $this->calibre->titleDetailsFilteredOpds($tl['entries']);
        $books = checkThumbnailOpds($books1, $this->bbs, $this->calibre, $this->config[AppConstants::THUMB_GEN_CLIPPED]);
        $gen = mkOpdsGenerator($this, $request);
        $cat = $gen->titlesCatalog(null, $books, false,
            $tl['page'], getNextSearchPage($tl), getLastSearchPage($tl));
        return mkOpdsResponse($response, $cat, OpdsGenerator::OPDS_MIME_ACQ);
    });
    /**
     * Return a page with author names initials
     */
    $this->get('/authorslist/', function (Psr\Http\Message\ServerRequestInterface $request, $response, $args) {
        $initials = $this->calibre->authorsInitials();
        $gen = mkOpdsGenerator($this, $request);
        $cat = $gen->authorsRootCatalog(null, $initials);
        return mkOpdsResponse($response, $cat, OpdsGenerator::OPDS_MIME_NAV);
    });
    /**
     * Return a page with author names for a initial
     * @param string $initial single uppercase character
     */
    $this->get('/authorslist/{initial}/', function (Psr\Http\Message\ServerRequestInterface $request, $response, $args) {
        $initial = $args['initial'];
        // parameter checking
        if (!(ctype_upper($initial))) {
            $this->logger->warn('opdsByAuthorNamesForInitial: invalid initial ' . $initial);
            return $response->withStatus(400)->write('Bad parameter');
        }
        $authors = $this->calibre->authorsNamesForInitial($initial);
        $gen = mkOpdsGenerator($this, $request);
        $cat = $gen->authorsNamesForInitialCatalog(null, $authors, $initial);
        return mkOpdsResponse($response, $cat, OpdsGenerator::OPDS_MIME_NAV);
    });
    /**
     * Return a feed with partial acquisition entries for the author's books
     * @param  string    initial initial character
     * @param  int        id      author id
     * @param  int        page    page number
     */
    $this->get('/authorslist/{initial}/{id}/{page}/', function (Psr\Http\Message\ServerRequestInterface $request, $response, $args) {
        $initial = $args['initial'];
        $id = $args['id'];
        $page = $args['page'];
        // parameter checking
        if (!is_numeric($id) || !is_numeric($page)) {
            $this->logger->warn('opdsByAuthor: invalid author id ' . $id . ' or page id ' . $page);
            return $response->withStatus(400)->write('Bad parameter');
        }
        $filter = getFilter($this);
        $lang = $this->l10n->user_lang || 'en';
        $psize = $this->config[AppConstants::PAGE_SIZE];
        $tl = $this->calibre->authorDetailsSlice($lang, $id, $page, $psize, $filter);
        $books1 = $this->calibre->titleDetailsFilteredOpds($tl['entries']);
        $books = checkThumbnailOpds($books1, $this->bbs, $this->calibre, $this->config[AppConstants::THUMB_GEN_CLIPPED]);
        $gen = mkOpdsGenerator($this, $request);
        $cat = $gen->booksForAuthorCatalog(null, $books, $initial, $tl['author'], false,
            $tl['page'], getNextSearchPage($tl), getLastSearchPage($tl));
        return mkOpdsResponse($response, $cat, OpdsGenerator::OPDS_MIME_ACQ);
    });
    /**
     * Return a page with series initials
     */
    $this->get('/serieslist/', function (Psr\Http\Message\ServerRequestInterface $request, $response, $args) {
        $initials = $this->calibre->seriesInitials();
        $gen = mkOpdsGenerator($this, $request);
        $cat = $gen->seriesRootCatalog(null, $initials);
        return mkOpdsResponse($response, $cat, OpdsGenerator::OPDS_MIME_NAV);
    });
    /**
     * Return a page with author names for a initial
     * @param string $initial "all" or single uppercase character
     */
    $this->get('/serieslist/{initial}/', function (Psr\Http\Message\ServerRequestInterface $request, $response, $args) {
        $initial = $args['initial'];
        // parameter checking
        if (!($initial == 'all' || ctype_upper($initial))) {
            $this->logger->warn('opdsBySeriesNamesForInitial: invalid initial ' . $initial);
            return $response->withStatus(400)->write('Bad parameter');
        }

        $tags = $this->calibre->seriesNamesForInitial($initial);
        $gen = mkOpdsGenerator($this, $request);
        $cat = $gen->seriesNamesForInitialCatalog(null, $tags, $initial);
        return mkOpdsResponse($response, $cat, OpdsGenerator::OPDS_MIME_NAV);

    });
    /**
     * Return a feed with partial acquisition entries for the series' books
     * @param  string    initial initial character
     * @param  int        id        tag id
     * @param  int        page    page index
     */
    $this->get('/serieslist/{initial}/{id}/{page}/', function (Psr\Http\Message\ServerRequestInterface $request, $response, $args) {
        $initial = $args['initial'];
        $id = $args['id'];
        $page = $args['page'];
        // parameter checking
        if (!is_numeric($id) || !is_numeric($page)) {
            $this->logger->warn('opdsBySeries: invalid series id ' . $id . ' or page id ' . $page);
            return $response->withStatus(400)->write('Bad parameter');
        }

        $filter = getFilter($this);
        $lang = $this->l10n->user_lang || 'en';
        $psize = $this->config[AppConstants::PAGE_SIZE];
        $tl = $this->calibre->seriesDetailsSlice($lang, $id, $page, $psize, $filter);
        $books1 = $this->calibre->titleDetailsFilteredOpds($tl['entries']);
        $books = checkThumbnailOpds($books1, $this->bbs, $this->calibre, $this->config[AppConstants::THUMB_GEN_CLIPPED]);
        $gen = mkOpdsGenerator($this, $request);
        $cat = $gen->booksForSeriesCatalog(null, $books, $initial, $tl['series'], false,
            $tl['page'], getNextSearchPage($tl), getLastSearchPage($tl));
        return mkOpdsResponse($response, $cat, OpdsGenerator::OPDS_MIME_ACQ);
    });
    /**
     * Create and send the catalog page for the current search criteria.
     * The search criteria is a GET parameter string.
     *
     * @param  integer $index index of page in search
     */
    $this->get('/searchlist/{page}/', function (Psr\Http\Message\ServerRequestInterface $request, $response, $args) {
        $index = $args['page'];
        // parameter checking
        if (!is_numeric($index)) {
            $this->logger->warn('opdsBySearch: invalid page id ' . $index);
            return $response->withStatus(400)->write('Bad parameter');
        }
        $params = $request->getQueryParams();
        if (!isset($params['search'])) {
            $this->logger->error('opdsBySearch called without search criteria, page ' . $index);
            return $response->withStatus(400)->write('Bad parameter');
        }
        $filter = getFilter($this);
        $lang = $this->l10n->user_lang || 'en';
        $psize = $this->config[AppConstants::PAGE_SIZE];
        $tl = $this->calibre->titlesSlice($lang, $index, $psize, $filter, $params['search']);
        $books1 = $this->calibre->titleDetailsFilteredOpds($tl['entries']);
        $books = checkThumbnailOpds($books1, $this->bbs, $this->calibre, $this->config[AppConstants::THUMB_GEN_CLIPPED]);
        $gen = mkOpdsGenerator($this, $request);
        $cat = $gen->searchCatalog(null, $books, false,
            $tl['page'], getNextSearchPage($tl), getLastSearchPage($tl), $params['search'],
            $tl['total'], $psize);
        return mkOpdsResponse($response, $cat, OpdsGenerator::OPDS_MIME_ACQ);
    });
    /**
     * Return a page with tag initials
     */
    $this->get('/tagslist/', function (Psr\Http\Message\ServerRequestInterface $request, $response, $args) {
        $initials = $this->calibre->tagsInitials();
        $gen = mkOpdsGenerator($this, $request);
        $cat = $gen->tagsRootCatalog(null, $initials);
        return mkOpdsResponse($response, $cat, OpdsGenerator::OPDS_MIME_NAV);
    });
    /**
     * Return a page with author names for a initial
     * @param string $initial single uppercase character
     */
    $this->get('/tagslist/{initial}/', function (Psr\Http\Message\ServerRequestInterface $request, $response, $args) {
        $initial = $args['initial'];
        // parameter checking
        if (!(ctype_upper($initial))) {
            $this->logger->warn('opdsByTagNamesForInitial: invalid initial ' . $initial);
            return $response->withStatus(400)->write('Bad parameter');
        }

        $tags = $this->calibre->tagsNamesForInitial($initial);
        $gen = mkOpdsGenerator($this, $request);
        $cat = $gen->tagsNamesForInitialCatalog(null, $tags, $initial);
        return mkOpdsResponse($response, $cat, OpdsGenerator::OPDS_MIME_NAV);
    });
    /**
     * Return a feed with partial acquisition entries for the tags's books
     * @param  string $initial initial character
     * @param  int $id tag id
     * @param  int $page page index
     */
    $this->get('/tagslist/{initial}/{id}/{page}/', function (Psr\Http\Message\ServerRequestInterface $request, $response, $args) {
        $initial = $args['initial'];
        $id = $args['id'];
        $page = $args['page'];
        // parameter checking
        if (!is_numeric($id) || !is_numeric($page)) {
            $this->logger->warn('opdsByTag: invalid series id ' . $id . ' or page id ' . $page);
            return $response->withStatus(400)->write('Bad parameter');
        }
        $filter = getFilter($this);
        $lang = $this->l10n->user_lang || 'en';
        $psize = $this->config[AppConstants::PAGE_SIZE];
        $tl = $this->calibre->tagDetailsSlice($lang, $id, $page, $psize, $filter);
        $books1 = $this->calibre->titleDetailsFilteredOpds($tl['entries']);
        $books = checkThumbnailOpds($books1, $this->bbs, $this->calibre, $this->config[AppConstants::THUMB_GEN_CLIPPED]);
        $gen = mkOpdsGenerator($this, $request);
        $cat = $gen->booksForTagCatalog(null, $books, $initial, $tl['tag'], false,
            $tl['page'], getNextSearchPage($tl), getLastSearchPage($tl));
        return mkOpdsResponse($response, $cat, OpdsGenerator::OPDS_MIME_ACQ);
    });


    /**
     * Return the cover for the book with ID. Calibre generates only JPEGs, so we always return a JPEG.
     * If there is no cover, return 404.
     */
    $this->get('/titles/{id}/cover/', function (Psr\Http\Message\ServerRequestInterface $request, $response, $args) {
        $id = $args['id'];
        $this->logger->debug('opds cover ' . $id);
        // parameter checking
        if (!is_numeric($id)) {
            $this->logger->warn('cover: invalid title id ' . $id);
            return $response->withStatus(400)->write('Bad parameter');
        }
        $book = $this->calibre->title($id);
        if (is_null($book)) {
            $this->logger->debug("cover: book not found: " . $id);
            return $response->withStatus(404)->write('Book not found');
        }
        if ($book->has_cover) {
            $cover = $this->calibre->titleCover($id);
            $fh = fopen($cover, 'rb');
            $stream = (new StreamFactory())->createStreamFromResource($fh); // create a stream instance for the response body
            return $response->withStatus(200)
                ->withHeader('Content-Type', 'image/jpeg;base64')
                ->withHeader('Content-Transfer-Encoding', 'binary')
                ->withHeader('Content-Length', filesize($cover))
                ->withBody($stream); // all stream contents will be sent to the response
        } else {
            return $response->withStatus(404)->write('No cover');
        }
    });

    /**
     * Return the selected file for the book with ID.
     */
    $this->get('/titles/{id}/format/{format}/', function (Psr\Http\Message\ServerRequestInterface $request, $response, $args) {
        $id = $args['id'];
        $format = $args['format'];
        $this->logger->debug('/opds/titles/file downloading ' . $format);
        // parameter checking
        if (!is_numeric($id)) {
            $this->logger->warn('book: invalid title id ' . $id);
            return $response->withStatus(400)->write('Bad parameter');
        }
        // TODO check file parameter?

        $lang = $this->l10n->user_lang || 'en';
        $details = $this->calibre->titleDetails($lang, $id);
        if (is_null($details)) {
            $this->logger->warn("book: no book found for " . $id);
            return $response->withStatus(404);
        }
        // for people trying to circumvent filtering by direct access
        if (title_forbidden($this->config[AppConstants::LOGIN_REQUIRED], $this->user, $details)) {
            $this->logger->warn("book: requested book not allowed for user: " . $id);
            return $response->withStatus(403);
        }

        $real_bookpath = $this->calibre->titleFileByFormat($id, $format);
        $contentType = Utilities::titleMimeType($real_bookpath);
        if (!is_null($this->user))
            $user = $this->username;
        else
            $user = '<unauthenticated user>';
        $metadata_update = $this->config[AppConstants::METADATA_UPDATE];
        $this->logger->info("book download by $user for $real_bookpath  with metadata update = $metadata_update");
        if ($contentType == Utilities::MIME_EPUB && $metadata_update) {
            if ($details['book']->has_cover == 1)
                $cover = $this->calibre->titleCover($id);
            else
                $cover = null;
            // If an EPUB update the metadata
            $mdep = new MetadataEpub($real_bookpath);
            $mdep->updateMetadata($details, $cover);
            $bookpath = $mdep->getUpdatedFile();
        } else {
            $bookpath = $real_bookpath;
        }
        $this->logger->debug("book(e): file " . $bookpath);
        $this->logger->debug("book(e): type " . $contentType);
        $booksize = filesize($bookpath);
        $this->logger->debug("book(e): size " . $booksize);
        $fh = fopen($bookpath, 'rb');
        $stream = (new StreamFactory())->createStreamFromResource($fh); // create a stream instance for the response body
        return $response
            ->withHeader('Content-Type', 'application/force-download')
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Type', 'application/download')
            ->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Type', $contentType)
            ->withHeader('Content-Length', $booksize)
            ->withHeader('Content-Transfer-Encoding', 'binary')
            ->withHeader('Content-Disposition', 'attachment; filename="' . basename($real_bookpath) . '"')
            // TODO no caching for book files?
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->withHeader('Pragma', 'public')
            ->withBody($stream); // all stream contents will be sent to the response
    });

});
