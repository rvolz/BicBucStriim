<?php
/**
 * BicBucStriim
 *
 * Copyright 2012-2013 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace App\Domain\Calibre;

use App\Domain\Calibre\Author;
use App\Domain\Calibre\AuthorBook;
use App\Domain\Calibre\Book;
use App\Domain\Calibre\BookAuthorLink;
use App\Domain\Calibre\BookLanguageLink;
use App\Domain\Calibre\BooksCustomColumnLink;
use App\Domain\Calibre\BookSeriesLink;
use App\Domain\Calibre\BookTagLink;
use App\Domain\Calibre\CalibreSearchType;
use App\Domain\Calibre\Comment;
use App\Domain\Calibre\CustomColumns;
use App\Domain\Calibre\Data;
use App\Domain\Calibre\Identifier;
use App\Domain\Calibre\Item;
use App\Domain\Calibre\Language;
use App\Domain\Calibre\Series;
use App\Domain\Calibre\SeriesBook;
use App\Domain\Calibre\Tag;
use App\Domain\Calibre\TagBook;
use App\Domain\Calibre\Utilities;
use \PDO;
use \Locale;
use AnyAscii;

class Calibre implements CalibreRepository
{

    # Thumbnail dimension (they are square)
    const THUMB_RES = 160;

    # last sqlite error
    public $last_error = 0;

    # calibre sqlite db
    protected ?PDO $calibre = null;
    # calibre library dir
    public string $calibre_dir = '';
    # calibre library file, last modified date
    public $calibre_last_modified;
    # dir for generated thumbs
    protected string $thumb_dir = '';

    /**
     * Check if the Calibre DB is readable
     * @param  string $path Path to Calibre DB
     * @return boolean            true if exists and is readable, else false
     */
    static function checkForCalibre($path)
    {
        $rp = realpath($path);
        $rpm = $rp . '/metadata.db';
        return is_readable($rpm);
    }

    /**
     * Open the Calibre DB.
     * @param string $calibrePath Complete path to Calibre library file
     * @param string $thumbDir Directory name for thumbnail files
     * @param bool $simulate fake the construction if true
     */
    function __construct($calibrePath, $thumbDir = './data', $simulate = false)
    {
        if ($simulate) {
            $this->calibre = NULL;
        } else {
            $rp = realpath($calibrePath);
            $this->calibre_dir = dirname($rp);
            $this->thumb_dir = $thumbDir;
            if (file_exists($rp) && is_readable($rp)) {
                $this->calibre_last_modified = filemtime($rp) ? filemtime($rp) : 0;
                $this->calibre = new PDO('sqlite:' . $rp, NULL, NULL, array());
                $this->calibre->setAttribute(1002, 'SET NAMES utf8');
                $this->calibre->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->calibre->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $this->last_error = $this->calibre->errorCode();
                $this->calibre->sqliteCreateFunction('transliterated', array($this, 'mkTransliteration'), 1);
            } else {
                $this->calibre = NULL;
            }
        }
    }

    function libraryOk(): bool
    {
        return !is_null($this->calibre);
    }

    public function getModTime(): int {
        return $this->calibre_last_modified;
    }

    function libraryStats(object $filter): array
    {
        $stats = array();
        $countParams = $this->mkCountParams(null, $filter, null);
        $queryFilter = $filter->getBooksFilter();
        $stats["titles"] = $this->count($this->mkBooksCount($queryFilter, false), $countParams);
        $stats["authors"] = $this->count($this->mkAuthorsCount($queryFilter, false), array());
        $stats["tags"] = $this->count($this->mkTagsCount($queryFilter, false), array());
        $stats["series"] = $this->count($this->mkSeriesCount($queryFilter, false), array());
        return $stats;
    }

    /**
     * Execute a query $sql on the Calibre DB and return the result
     * as an array of objects of class $class
     *
     * @param  [type] $class [description]
     * @param  [type] $sql   [description]
     * @return [type]        [description]
     * @deprecated
     */
    protected
    function find($class, $sql)
    {
        $stmt = $this->calibre->query($sql, PDO::FETCH_CLASS, $class);
        $this->last_error = $stmt->errorCode();
        $items = $stmt->fetchAll();
        $stmt->closeCursor();
        return $items;
    }

    /**
     * Execute a query $sql on the Calibre DB and return the result
     * as an array of objects of class $class
     *
     * @param string $class Calibre item class name
     * @param string $sql SQL statement
     * @param array $params array of query parameters
     * @return array found items
     */
    protected
    function findPrepared(string $class, string $sql, array $params): array
    {
        $stmt = $this->calibre->prepare($sql);
        $stmt->execute($params);
        $this->last_error = $stmt->errorCode();
        $items = $stmt->fetchAll(PDO::FETCH_CLASS, $class);
        $stmt->closeCursor();
        return $items;
    }

    /**
     * Return a single object or NULL if not found
     * @param string $class Calibre Item class
     * @param string $sql SQL statement
     * @param array $params array of query parameters
     * @return ?object instance of class $class or NULL
     */
    protected function findOne(string $class, string $sql, $params = array()): ?object
    {
        $result = $this->findPrepared($class, $sql, $params);
        if ($result == NULL || $result == FALSE)
            return NULL;
        else
            return $result[0];
    }

    private function mkCountParams($id, $filter, $search)
    {
        $params = array();
        if (!is_null($id)) {
            $params['id'] = $id;
        }
        if (!is_null($filter->tag_id)) {
            $params['tag'] = $filter->tag_id;
        }
        if (!is_null($filter->lang_id)) {
            $params['lang'] = $filter->lang_id;
        }
        if (!is_null($search)) {
            $params['search_l'] = '%' . mb_convert_case($search, MB_CASE_LOWER, 'UTF-8') . '%';
            $params['search_t'] = '%' . mb_convert_case($search, MB_CASE_TITLE, 'UTF-8') . '%';
        }
        return $params;
    }

    private function mkQueryParams($id, $filter, $search, $length, $offset)
    {
        $params = array();
        if (!is_null($id)) {
            $params['id'] = $id;
        }
        if (!is_null($filter->tag_id)) {
            $params['tag'] = $filter->tag_id;
        }
        if (!is_null($filter->lang_id)) {
            $params['lang'] = $filter->lang_id;
        }
        if (!is_null($search)) {
            $params['search_l'] = '%' . mb_convert_case($search, MB_CASE_LOWER, 'UTF-8') . '%';
            $params['search_t'] = '%' . mb_convert_case($search, MB_CASE_TITLE, 'UTF-8') . '%';
        }
        if (!is_null($length)) {
            $params['length'] = $length;
        }
        if (!is_null($offset)) {
            $params['offset'] = $offset;
        }
        return $params;
    }

    /**
     * Return a slice of entries defined by the parameters $index and $length.
     * If $search is defined it is used to filter the titles, ignoring case.
     * Return an array with elements: current page, no. of pages, $length entries
     *
     * @param int $searchType index of search type to use, see CalibreSearchType
     * @param integer $index page index
     * @param integer $length length of page
     * @param CalibreFilter $filter filter expression
     * @param SearchOptions|null $searchOptions =null
     * @param null $id =null         optional author/tag/series ID
     * @return array                            an array with current page (key 'page'),
     *                                          number of pages (key 'pages'),
     *                                          an array of $class instances (key 'entries') or NULL
     *
     * Changed thanks to QNAP who insist on publishing outdated libraries in their firmware
     * TODO revert back to real SQL, not the outdated-QNAP stlyle
     */
    protected function findSliceFiltered(int $searchType, int $index, int $length, CalibreFilter $filter, SearchOptions $searchOptions = null, $id = null): array
    {
        if ($index < 0 || $length < 1 || $searchType < CalibreSearchType::Author || $searchType > CalibreSearchType::LastModifiedOrderedBook)
            return array('page' => 0, 'pages' => 0, 'entries' => NULL);
        $offset = $index * $length;

        // TODO Integrate SearchOptions fully instead of emulating the old behaviour
        $searching = !is_null($searchOptions);
        $search = is_null($searchOptions) ? null : $searchOptions->getSearchTerm();
        $translit = is_null($searchOptions) ? false : $searchOptions->isUseAsciiTransliteration();

        $countParams = $this->mkCountParams($id, $filter, $search);
        $queryParams = $this->mkQueryParams($id, $filter, $search, $length, $offset);
        $queryFilter = $filter->getBooksFilter();
        switch ($searchType) {
            case CalibreSearchType::Author:
                $class = Author::class;
                $count = $this->mkAuthorsCount($queryFilter, $searching, $translit);
                if (is_null($search)) {
                    $query = 'SELECT a.id, a.name, a.sort, (SELECT COUNT(*) FROM books_authors_link b WHERE b.author=a.id) AS anzahl FROM authors AS a ORDER BY a.sort';
                } else {
                    if ($translit)
                        $where = 'lower(transliterated(a.name)) LIKE :search_l OR lower(transliterated(a.name)) LIKE :search_t';
                    else
                        $where = 'lower(a.name) LIKE :search_l OR lower(a.name) LIKE :search_t';
                    $query = "SELECT a.id, a.name, a.sort, (SELECT COUNT(*) FROM books_authors_link b WHERE b.author=a.id) AS anzahl FROM authors AS a WHERE {$where} ORDER BY a.sort";
                }
                break;
            case CalibreSearchType::AuthorBook:
                $class = AuthorBook::class;
                if (is_null($search)) {
                    $count = 'SELECT count(*) FROM (SELECT BAL.book, Books.* FROM books_authors_link BAL, ' . $queryFilter . ' Books WHERE Books.id=BAL.book AND author=:id)';
                    $query = 'SELECT BAL.book, Books.* FROM books_authors_link BAL, ' . $queryFilter . ' Books WHERE Books.id=BAL.book AND author=:id ORDER BY Books.sort';
                } else {
                    $count = 'SELECT count(*) FROM (SELECT BAL.book, Books.* FROM books_authors_link BAL, ' . $queryFilter . ' Books WHERE Books.id=BAL.book AND author=:id) WHERE Books.sort LIKE :search_l OR Books.sort LIKE :search_u OR Books.sort LIKE :search_t';
                    $query = 'SELECT BAL.book, Books.* FROM books_authors_link BAL, ' . $queryFilter . ' Books WHERE Books.id=BAL.book AND author=:id AND (lower(Books.sort) LIKE :search_l OR lower(Books.sort) LIKE :search_t) ORDER BY Books.sort';
                }
                break;
            case CalibreSearchType::Book:
                $class = Book::class;
                $count = $this->mkBooksCount($queryFilter, $searching, $translit);
                $query = $this->mkBooksQuery($searchType, true, $queryFilter, $searching, $translit);
                break;
            case CalibreSearchType::Series:
                $class = Series::class;
                $count = $this->mkSeriesCount($queryFilter, $searching, $translit);
                if (is_null($search)) {
                    $query = 'SELECT series.id, series.name, (SELECT COUNT(*) FROM books_series_link AS bsl WHERE series.id = bsl.series ) AS anzahl FROM series ORDER BY series.name';
                } else {
                    if ($translit)
                        $where = 'lower(transliterated(series.name)) LIKE :search_l OR lower(transliterated(series.name)) LIKE :search_t';
                    else
                        $where = 'lower(series.name) LIKE :search_l OR lower(series.name) LIKE :search_t';
                    $query = "SELECT series.id, series.name, (SELECT COUNT(*) FROM books_series_link AS bsl WHERE series.id = bsl.series ) AS anzahl FROM series WHERE {$where} ORDER BY series.name";
                }
                break;
            case CalibreSearchType::SeriesBook:
                $class = SeriesBook::class;
                if (is_null($search)) {
                    $count = 'SELECT count (*) FROM (SELECT BSL.book, Books.* FROM books_series_link BSL, ' . $queryFilter . ' Books WHERE Books.id=BSL.book AND series=:id)';
                    $query = 'SELECT BSL.book, Books.* FROM books_series_link BSL, ' . $queryFilter . ' Books WHERE Books.id=BSL.book AND series=:id ORDER BY series_index';
                } else {
                    $count = 'SELECT count (*) FROM (SELECT BSL.book, Books.* FROM books_series_link BSL, ' . $queryFilter . ' Books WHERE Books.id=BSL.book AND series=:id) WHERE sort LIKE :search_l OR sort LIKE :search_u OR sort LIKE :search_t';
                    $query = 'SELECT BSL.book, Books.* FROM books_series_link BSL, ' . $queryFilter . ' Books WHERE Books.id=BSL.book AND series=:id AND (lower(Books.sort) LIKE :search_l OR lower(Books.sort) LIKE :search_t) ORDER BY series_index';
                }
                break;
            case CalibreSearchType::Tag:
                $class = Tag::class;
                $count = $this->mkTagsCount($queryFilter, $searching, $translit);
                if (is_null($search)) {
                    $query = 'SELECT tags.id, tags.name, (SELECT COUNT(*) FROM books_tags_link AS btl WHERE tags.id = btl.tag) AS anzahl FROM tags ORDER BY tags.name';
                } else {
                    if ($translit)
                        $where = 'lower(transliterated(tags.name)) LIKE :search_l OR lower(transliterated(tags.name)) LIKE :search_t';
                    else
                        $where = 'lower(tags.name) LIKE :search_l OR lower(tags.name) LIKE :search_t';
                    $query = "SELECT tags.id, tags.name, (SELECT COUNT(*) FROM books_tags_link AS btl WHERE tags.id = btl.tag) AS anzahl FROM tags WHERE {$where} ORDER BY tags.name";
                }
                break;
            case CalibreSearchType::TagBook:
                $class = TagBook::class;
                if (is_null($search)) {
                    $count = 'SELECT count (*) FROM (SELECT BTL.book, Books.* FROM books_tags_link BTL, ' . $queryFilter . ' Books WHERE Books.id=BTL.book AND tag=:id)';
                    $query = 'SELECT BTL.book, Books.* FROM books_tags_link BTL, ' . $queryFilter . ' Books WHERE Books.id=BTL.book AND tag=:id ORDER BY Books.sort';
                } else {
                    $count = 'SELECT count (*) FROM (SELECT BTL.book, Books.* FROM books_tags_link BTL, ' . $queryFilter . ' Books WHERE Books.id=BTL.book AND tag=:id) WHERE sort LIKE :search_l OR sort LIKE :search_u OR sort LIKE :search_t';
                    $query = 'SELECT BTL.book, Books.* FROM books_tags_link BTL, ' . $queryFilter . ' Books WHERE Books.id=BTL.book AND tag=:id AND (lower(Books.sort) LIKE :search_l OR lower(Books.sort) LIKE :search_t) ORDER BY Books.sort';
                }
                break;
            case CalibreSearchType::TimestampOrderedBook:
            case CalibreSearchType::PubDateOrderedBook:
            case CalibreSearchType::LastModifiedOrderedBook:
                $class = Book::class;
                $count = $this->mkBooksCount($queryFilter, $searching);
                $query = $this->mkBooksQuery($searchType, false, $queryFilter, $searching);
                break;
        }
        $query = $query . ' limit :length offset :offset';
        $no_entries = $this->count($count, $countParams);
        if ($no_entries > 0) {
            $no_pages = (int)($no_entries / $length);
            if ($no_entries % $length > 0)
                $no_pages += 1;
            $entries = $this->findPrepared($class, $query, $queryParams);
        } else {
            $no_pages = 0;
            $entries = array();
        }
        return array('page' => $index, 'pages' => $no_pages, 'entries' => $entries, 'total' => $no_entries);
    }

    /**
     * Generate a SQL query for selecting books ordered by various fields
     * @param int $searchType
     * @param boolean $sortAscending ASC, result should be sorted ASC or DESC?
     * @param string $queryFilter
     * @param ?string $search optional search string
     * @param bool $translit if true use transliteration for search term
     * @return string                               SQL query
     */
    private function mkBooksQuery(int $searchType, bool $sortAscending, string $queryFilter, $search = null, $translit = false): string
    {
        switch ($searchType) {
            case CalibreSearchType::Book:
                $sortField = 'sort';
                break;
            case CalibreSearchType::TimestampOrderedBook:
                $sortField = 'timestamp';
                break;
            case CalibreSearchType::PubDateOrderedBook:
                $sortField = 'pubdate';
                break;
            case CalibreSearchType::LastModifiedOrderedBook:
                $sortField = 'last_modified';
                break;
        }
        if ($sortAscending) {
            $sortModifier = " ASC";
        } else {
            $sortModifier = " DESC";
        }
        if ($search) {
            if ($translit)
                $where = 'lower(transliterated(title)) LIKE :search_l OR lower(transliterated(title)) LIKE :search_t';
            else
                $where = 'lower(title) LIKE :search_l OR lower(title) LIKE :search_t';
            $query = 'SELECT * FROM ' . $queryFilter . " WHERE ${where} ORDER BY " . $sortField . ' ' . $sortModifier;
        } else {
            $query = 'SELECT * FROM ' . $queryFilter . ' ORDER BY ' . $sortField . ' ' . $sortModifier;
        }
        return $query;
    }

    private function mkBooksCount($queryFilter, $search = false, $translit = false)
    {
        if (!$search) {
            $count = 'SELECT count(*) FROM ' . $queryFilter;
        } else {
            if ($translit)
                $where = 'lower(transliterated(title)) LIKE :search_l OR lower(transliterated(title)) LIKE :search_t';
            else
                $where = 'lower(title) LIKE :search_l OR lower(title) LIKE :search_t';
            $count = "SELECT count(*) FROM {$queryFilter} WHERE {$where}";
        }
        return $count;
    }

    private function mkAuthorsCount($queryFilter, $search = null, $translit = false)
    {
        if (!$search) {
            $count = 'SELECT count(*) FROM authors';
        } else {
            if ($translit)
                $where = 'lower(transliterated(name)) LIKE :search_l OR lower(transliterated(name)) LIKE :search_t';
            else
                $where = 'lower(name) LIKE :search_l OR lower(name) LIKE :search_t';
            $count = "SELECT count(*) FROM authors WHERE {$where}";
        }
        return $count;
    }

    private function mkTagsCount($queryFilter, $search = null, $translit = false)
    {
        if (!$search) {
            $count = 'SELECT count(*) FROM tags';
        } else {
            if ($translit)
                $where = 'lower(transliterated(tags.name)) LIKE :search_l OR lower(transliterated(tags.name)) LIKE :search_t';
            else
                $where = 'lower(tags.name) LIKE :search_l OR lower(tags.name) LIKE :search_t';
            $count = "SELECT count(*) FROM tags WHERE {$where}";
        }
        return $count;
    }

    private function mkSeriesCount($queryFilter, $search = false, $translit = false)
    {
        if (!$search) {
            $count = 'SELECT count(*) FROM series';
        } else {
            if ($translit)
                $where = 'lower(transliterated(name)) LIKE :search_l OR lower(transliterated(name)) LIKE :search_t';
            else
                $where = 'lower(name) LIKE :search_l OR lower(name) LIKE :search_t';
            $count = "SELECT count(*) FROM series WHERE {$where}";
        }
        return $count;
    }

    public function mkTransliteration(string $name): string
    {
        return AnyAscii::transliterate($name);
    }

    function count(string $sql, array $params):int
    {
        $stmt = $this->calibre->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchColumn();
        if ($result == NULL || $result == FALSE)
            return 0;
        else
            return (int)$result;
    }

    public function getLanguageId($languageCode)
    {
        $result = $this->calibre->query('SELECT id FROM languages WHERE lang_code = "' . $languageCode . '"')->fetchColumn();
        if ($result == NULL || $result == FALSE)
            return NULL;
        else
            return $result;
    }

    public function getTagId(string $tagName): int
    {
        $result = $this->calibre->query('SELECT id FROM tags WHERE name = "' . $tagName . '"')->fetchColumn();
        if ($result == null || $result == false)
            return 0;
        else
            return $result;
    }

    function last30Books($lang, $nrOfTitles = 30, $filter)
    {
        $queryParams = $this->mkQueryParams(NULL, $filter, NULL, $nrOfTitles, NULL);
        $books = $this->findPrepared(Book::class, 'SELECT * FROM ' . $filter->getBooksFilter() . ' ORDER BY timestamp DESC LIMIT :length', $queryParams);
        $this->addBookDetails($lang, $books);
        return $books;
    }

    /**
     * Add formatted book language and formats info to a collection of books.
     * book->formats contains the list of available formats as a comma-separated string
     * book->language contains the book's language, only available if the PHP extension 'intl' is installed
     * book->addInfo contains a formatted string with language and formats, e.g. "(English; MOBI,PDF,EPUB)"
     * @param string $lang the target language code for the display
     * @param array $books array of books
     */
    protected function addBookDetails($lang, $books)
    {
        foreach ((array)$books as $book) {
            $fmts = $this->titleGetFormats($book->id);
            $fmtnames = array();
            foreach ($fmts as $format) {
                array_push($fmtnames, $format->format);
            }
            $book->formats = join(',', $fmtnames);
        }
        if (extension_loaded('intl')) {
            foreach ($books as $book) {
                $langcodes = $this->getLanguages($book->id);
                $langtexts = array();
                foreach ($langcodes as $langcode) {
                    $bol = Locale::getDisplayLanguage($langcode, $lang);
                    array_push($langtexts, $bol);
                }
                $book->language = join(',', $langtexts);
            }
        }
        foreach ((array)$books as $book) {
            if (empty($book->formats) && !isset($book->language))
                $book->addInfo = '';
            elseif (empty($book->formats) && isset($book->language))
                $book->addInfo = '(' . $book->language . ')';
            elseif (!empty($book->formats) && !isset($book->language))
                $book->addInfo = '(' . $book->formats . ')';
            else
                $book->addInfo = '(' . $book->language . '; ' . $book->formats . ')';
        }
    }

    public function author($id)
    {
        return $this->findOne(Author::class, 'SELECT * FROM authors WHERE id=:id', array('id' => $id));
    }

    function authorDetails($id)
    {
        $author = $this->findOne(Author::class, 'SELECT * FROM authors WHERE id=:id', array('id' => $id));
        if (is_null($author)) return NULL;
        $book_ids = $this->findPrepared(BookAuthorLink::class, 'SELECT * FROM books_authors_link WHERE author=:id',
            array('id' => $id));
        $books = array();
        foreach ($book_ids as $bid) {
            $book = $this->title($bid->book);
            array_push($books, $book);
        }
        return array('author' => $author, 'books' => $books);
    }

    function authorDetailsSlice($lang, $id, $index = 0, $length = 100, $filter)
    {
        $author = $this->findOne(Author::class, 'SELECT * FROM authors WHERE id=:id', array('id' => $id));
        if (is_null($author))
            return NULL;
        $slice = $this->findSliceFiltered(CalibreSearchType::AuthorBook, $index, $length, $filter, NULL, $id);
        $this->addBookDetails($lang, $slice['entries']);
        return array('author' => $author) + $slice;
    }

    function authorsSlice(int $index=0, int $length=100, SearchOptions $searchOptions=null): array
    {
        return $this->findSliceFiltered(CalibreSearchType::Author, $index, $length, new CalibreFilter(), $searchOptions, null);
    }

    function authorsInitials()
    {
        $initials = $this->findPrepared(Item::class,
            'SELECT DISTINCT substr(upper(sort),1,1) AS initial FROM authors ORDER BY initial ASC',
            array());
        $ret = array();
        foreach ($initials as $initial) {
            $i = new Item();
            $ctr = $this->findOne(Item::class, 'SELECT COUNT(*) as ctr FROM authors WHERE substr(upper(sort),1,1)=:initial', array('initial' => $initial->initial));
            $i->initial = $initial->initial;
            $i->ctr = $ctr->ctr;
            array_push($ret, $i);
        }
        return $ret;
    }

    function authorsNamesForInitial($initial)
    {
        return $this->findPrepared(Author::class,
            'SELECT a.id, a.name, a.sort, (SELECT COUNT(*) FROM books_authors_link AS bal WHERE a.id = bal.author) AS anzahl FROM authors AS a WHERE substr(upper(a.sort),1,1)=:initial ORDER BY a.sort',
            array('initial' => $initial));
    }

    /**
     * @inheritDoc
     */
    function authorSeries($id, $books)
    {
        $allSeriesIds = array();
        foreach ($books as $book) {
            $series_ids = $this->findPrepared(BookSeriesLink::class, 'SELECT * FROM books_series_link WHERE book=:id', array('id'=>$book->id));
            foreach ($series_ids as $sid) {
                array_push($allSeriesIds, $sid->series);
            }
        }
        $uniqueSeriesIds = array_unique($allSeriesIds);
        $series = array();
        foreach ($uniqueSeriesIds as $sid) {
            $this_series = $this->findOne(Series::class, 'SELECT * FROM series WHERE id=:id', array('id' => $sid));
            array_push($series, $this_series);
        }
        return $series;
    }

    function idTypes()
    {
        $stmt = $this->calibre->query('SELECT DISTINCT type FROM identifiers');
        $this->last_error = $stmt->errorCode();
        $items = $stmt->fetchAll();
        $stmt->closeCursor();
        return $items;
    }


    function languages()
    {
        return $this->findPrepared(Language::class, 'SELECT * FROM languages', array());
    }


    function tags()
    {
        return $this->findPrepared(Tag::class, 'SELECT * FROM tags ORDER BY name', array());
    }

    function tagDetails($id)
    {
        $tag = $this->findOne(Tag::class, 'SELECT * FROM tags WHERE id=:id', array('id' => $id));
        if (is_null($tag)) return NULL;
        $book_ids = $this->findPrepared(BookTagLink::class, 'SELECT * FROM books_tags_link WHERE tag=:id', array('id' => $id));
        $books = array();
        foreach ($book_ids as $bid) {
            $book = $this->title($bid->book);
            array_push($books, $book);
        }
        return array('tag' => $tag, 'books' => $books);
    }

    function tagDetailsSlice(string $lang, int $id, $index = 0, $length = 100, $filter): array
    {
        $tag = $this->findOne(Tag::class, 'SELECT * FROM tags WHERE id=:id', array('id' => $id));
        if (is_null($tag))
            return array();
        $slice = $this->findSliceFiltered(CalibreSearchType::TagBook, $index, $length, $filter, null, $id);
        $this->addBookDetails($lang, $slice['entries']);
        return array('tag' => $tag) + $slice;
    }

    /**
     * @inheritDoc
     */
    function tagsSlice($index = 0, $length = 100, $searchOptions = null): array
    {
        return $this->findSliceFiltered(CalibreSearchType::Tag, $index, $length, new CalibreFilter(), $searchOptions, null);
    }

    function tagsInitials()
    {
        $initials = $this->findPrepared(Item::class,
            'SELECT DISTINCT substr(upper(name),1,1) AS initial FROM tags ORDER BY initial ASC',
            array());
        $ret = array();
        foreach ($initials as $initial) {
            $i = new Item();
            $ctr = $this->findOne(Item::class, 'SELECT COUNT(*) as ctr FROM tags WHERE substr(upper(name),1,1)=:initial', array('initial' => $initial->initial));
            $i->initial = $initial->initial;
            $i->ctr = $ctr->ctr;
            array_push($ret, $i);
        }
        return $ret;
    }

    function tagsNamesForInitial($initial)
    {
        return $this->findPrepared(Tag::class,
            'SELECT tags.id, tags.name, (SELECT COUNT(*) FROM books_tags_link AS btl WHERE tags.id = btl.tag ) AS anzahl FROM tags WHERE substr(upper(tags.name),1,1)=:initial ORDER BY tags.name',
            array('initial' => $initial));
    }

    function pubdateOrderedTitlesSlice($lang, $index = 0, $length = 100, $filter, $search = null)
    {
        $books = $this->findSliceFiltered(CalibreSearchType::PubDateOrderedBook, $index, $length, $filter, $search);
        $this->addBookDetails($lang, $books['entries']);
        return $books;
    }

    function lastmodifiedOrderedTitlesSlice($lang, $index = 0, $length = 100, $filter, $search = null)
    {
        $books = $this->findSliceFiltered(CalibreSearchType::LastModifiedOrderedBook, $index, $length, $filter, $search);
        $this->addBookDetails($lang, $books['entries']);
        return $books;
    }

    function timestampOrderedTitlesSlice($lang, $index = 0, $length = 100, $filter, $search = null)
    {
        $books = $this->findSliceFiltered(CalibreSearchType::TimestampOrderedBook, $index, $length, $filter, $search);
        $this->addBookDetails($lang, $books['entries']);
        return $books;
    }

    function titlesSlice(string $lang, int $index, int $length, object $filter, $searchOptions = null): array
    {
        $books = $this->findSliceFiltered(CalibreSearchType::Book, $index, $length, $filter, $searchOptions, null);
        $this->addBookDetails($lang, $books['entries']);
        return $books;
    }


    /**
     * @inheritDoc
     */
    function title(int $id): object
    {
        $book = $this->findOne(Book::class, 'SELECT * FROM books WHERE id=:id', array('id' => $id));
        if (is_null($book))
            throw new TitleNotFoundException();
        else
            return $book;
    }

    /**
     * @inheritDoc
     */
    function titleCover(int $id): string
    {
        $book = $this->title($id);
        $cover_path = Utilities::bookPath($this->calibre_dir, $book->path, 'cover.jpg');
        if (is_null($cover_path))
            throw new CoverNotFoundException();
        else
            return $cover_path;
    }


    function getLanguage($book_id)
    {
        $lang_code = null;
        $lang_id = $this->findOne(BookLanguageLink::class, 'SELECT * FROM books_languages_link WHERE book=:id', array('id' => $book_id));
        if (!is_null($lang_id))
            $lang_code = $this->findOne(Language::class, 'SELECT * FROM languages WHERE id=:id', array('id' => $lang_id->lang_code));
        if (is_null($lang_code))
            $lang_text = '';
        else
            $lang_text = $lang_code->lang_code;
        return $lang_text;
    }

    function getLanguages($book_id)
    {
        $lang_codes = array();
        $lang_ids = $this->findPrepared(BookLanguageLink::class, 'SELECT * FROM books_languages_link WHERE book=:id', array('id' => $book_id));
        foreach ($lang_ids as $lang_id) {
            $lang_code = $this->findOne(Language::class, 'SELECT * FROM languages WHERE id=:id', array('id' => $lang_id->lang_code));
            if (!is_null($lang_code))
                array_push($lang_codes, $lang_code->lang_code);
        }
        return $lang_codes;
    }

    function titleDetails($lang, $id): array
    {
        $book = $this->title($id);
        $author_ids = $this->findPrepared(BookAuthorLink::class, 'SELECT * FROM books_authors_link WHERE book=:id',
            array('id' => $id));
        $authors = array();
        foreach ($author_ids as $aid) {
            $author = $this->findOne(Author::class, 'SELECT * FROM authors WHERE id=:id', array('id' => $aid->author));
            array_push($authors, $author);
        }
        $series_ids = $this->findPrepared(BookSeriesLink::class, 'SELECT * FROM books_series_link WHERE book=:id',
            array('id' => $id));
        $series = array();
        foreach ($series_ids as $aid) {
            $this_series = $this->findOne(Series::class, 'SELECT * FROM series WHERE id=:id', array('id' => $aid->series));
            array_push($series, $this_series);
        }
        $tag_ids = $this->findPrepared(BookTagLink::class, 'SELECT * FROM books_tags_link WHERE book=:id',
            array('id' => $id));
        $tags = array();
        foreach ($tag_ids as $tid) {
            $tag = $this->findOne(Tag::class, 'SELECT * FROM tags WHERE id=:id', array('id' => $tid->tag));
            array_push($tags, $tag);
        }
        $langcodes = $this->getLanguages($id);
        if (extension_loaded('intl')) {
            $langtexts = array();
            foreach ($langcodes as $langcode) {
                $bol = Locale::getDisplayLanguage($langcode, $lang);
                array_push($langtexts, $bol);
            }
            $language = join(', ', $langtexts);
        } else {
            $language = null;
        }
        $formats = $this->findPrepared(Data::class, 'SELECT * FROM data WHERE book=:id',
            array('id' => $id));
        $comment = $this->findOne(Comment::class, 'SELECT * FROM comments WHERE book=:id', array('id' => $id));
        $ids = $this->findPrepared(Identifier::class, 'SELECT * FROM identifiers WHERE book=:id',
            array('id' => $id));
        if (is_null($comment))
            $comment_text = '';
        else
            $comment_text = $comment->text;
        $customColumns = $this->customColumns($id);
        return array('book' => $book,
            'authors' => $authors,
            'series' => $series,
            'tags' => $tags,
            'formats' => $formats,
            'comment' => $comment_text,
            'language' => $language,
            'langcodes' => $langcodes,
            'custom' => $customColumns,
            'ids' => $ids);
    }

    function titleDetailsMini($id)
    {
        $book = $this->title($id);
        if (is_null($book)) return NULL;
        $tag_ids = $this->findPrepared(BookTagLink::class, 'SELECT * FROM books_tags_link WHERE book=:id', array('id' => $id));
        $tags = array();
        foreach ($tag_ids as $tid) {
            $tag = $this->findOne(Tag::class, 'SELECT * FROM tags WHERE id=:id', array('id' => $tid->tag));
            array_push($tags, $tag);
        }
        $langcodes = $this->getLanguages($id);
        return array('book' => $book,
            'tags' => $tags,
            'langcodes' => $langcodes);
    }


    # Add a new cc value. If the key already exists, combine the values with a string join.
    private function addCc($def, $value, $result)
    {
        if (array_key_exists($def->name, $result)) {
            $oldv = $result[$def->name];
            $oldv['value'] = $oldv['value'] . ', ' . $value;
            $result[$def->name] = $oldv;
        } else
            $result[$def->name] = array('name' => $def->name, 'type' => $def->datatype, 'value' => $value);
        return $result;
    }

    function customColumns($book_id)
    {
        $columns = $this->findPrepared(CustomColumns::class, 'SELECT * FROM custom_columns ORDER BY name', array());
        $ccs = array();
        foreach ($columns as $column) {
            $column_id = $column->id;
            if ($column->datatype == 'composite' || $column->datatype == 'series') {
                # composites have no data in the tables; they are template expressions
                # that are apparently evalued dynamically, so we ignore them
                # series contain two data values -- one in the link table, one in the cc table -- handling?
                continue;
            } else if ($column->datatype == 'text' || $column->datatype == 'enumeration' || $column->datatype == 'rating') {
                # these have extra link tables
                $lvs = $this->findPrepared(BooksCustomColumnLink::class, 'SELECT * FROM books_custom_column_' . $column_id . '_link WHERE book=:id',
                    array('id' => $book_id));
                foreach ($lvs as $lv) {
                    $cvs = $this->findPrepared(CustomColumns::class, 'SELECT * FROM custom_column_' . $column_id . ' WHERE id=:id',
                        array('id' => $lv->value));
                    foreach ($cvs as $cv) {
                        $ccs = $this->addCc($column, $cv->value, $ccs);
                    }
                }
            } else {
                # these need just the cc table
                $cvs = $this->findPrepared(CustomColumns::class, 'SELECT * FROM custom_column_' . $column_id . ' WHERE book=:id',
                    array('id' => $book_id));
                foreach ($cvs as $cv) {
                    $ccs = $this->addCc($column, $cv->value, $ccs);
                }
            }
        }

        return $ccs;
    }

    function titleDetailsOpds($book)
    {
        if (is_null($book)) return NULL;
        $author_ids = $this->findPrepared(BookAuthorLink::class, 'SELECT * FROM books_authors_link WHERE book=:id', array('id' => $book->id));
        $authors = array();
        foreach ($author_ids as $aid) {
            $author = $this->findOne(Author::class, 'SELECT * FROM authors WHERE id=:id', array('id' => $aid->author));
            array_push($authors, $author);
        }
        $tag_ids = $this->findPrepared(BookTagLink::class, 'SELECT * FROM books_tags_link WHERE book=:id', array('id' => $book->id));
        $tags = array();
        foreach ($tag_ids as $tid) {
            $tag = $this->findOne(Tag::class, 'SELECT * FROM tags WHERE id=:id', array('id' => $tid->tag));
            array_push($tags, $tag);
        }
        $lang_id = $this->findOne(BookLanguageLink::class, 'SELECT * FROM books_languages_link WHERE book=:id', array('id' => $book->id));
        if (is_null($lang_id))
            $lang_text = '';
        else {
            $lang_code = $this->findOne(Language::class, 'SELECT * FROM languages WHERE id=:id', array('id' => $lang_id->lang_code));
            if (is_null($lang_code))
                $lang_text = '';
            else
                $lang_text = $lang_code->lang_code;
        }
        $comment = $this->findOne(Comment::class, 'SELECT * FROM comments WHERE book=:id', array('id' => $book->id));
        if (is_null($comment))
            $comment_text = '';
        else
            $comment_text = $comment->text;
        # Strip html excluding the most basic tags and remove all tag attributes
        $comment_text = strip_tags($comment_text, '<div><strong><i><em><b><p><br><br/>');
        $comment_text = preg_replace("/<([a-z][a-z0-9]*)[^>]*?(\/?)>/i", '<$1$2>', $comment_text);
        $formats = $this->findPrepared(Data::class, 'SELECT * FROM data WHERE book=:id', array('id' => $book->id));
        return array('book' => $book, 'authors' => $authors, 'tags' => $tags,
            'formats' => $formats, 'comment' => $comment_text, 'language' => $lang_text);
    }

    function titleDetailsFilteredOpds($books)
    {
        $filtered_books = array();
        foreach ($books as $book) {
            $record = $this->titleDetailsOpds($book);
            if (!empty($record['formats']))
                array_push($filtered_books, $record);
        }
        return $filtered_books;
    }

    function titleFile($id, $file)
    {
        $book = $this->title($id);
        if (is_null($book))
            return NULL;
        else
            return Utilities::bookPath($this->calibre_dir, $book->path, $file);
    }

    function titleFileByFormat($id, $format)
    {
        $book = $this->title($id);
        if (is_null($book))
            return NULL;
        else {
            $xformat = $this->findOne(Data::class, 'SELECT * FROM data WHERE book=:id AND format=:format',
                array('id' => $id, 'format' => $format));
            $file = $xformat->name . '.' . strtolower($format);
            return Utilities::bookPath($this->calibre_dir, $book->path, $file);
        }
    }

    function titleGetFormats($bookid): array
    {
        return $this->findPrepared(Data::class, 'SELECT * FROM data WHERE book=:id', array('id' => $bookid));
    }

    function titleGetKindleFormat($id)
    {
        $book = $this->title($id);
        if (is_null($book)) return NULL;
        $formats = $this->findPrepared(Data::class,
            "SELECT * FROM data WHERE book=:id AND (format='AZW' OR format='AZW3' OR format='MOBI' OR format='HTML' OR format='PDF')",
            array('id' => $id));
        if (empty($formats))
            return NULL;
        else {
            usort($formats, array($this, 'kindleFormatSort'));
            $format = $formats[0];
        }
        return $format;
    }

    function seriesDetails($id)
    {
        $series = $this->findOne(Series::class, 'SELECT * FROM series WHERE id=:id', array('id' => $id));
        if (is_null($series)) return NULL;
        $books = $this->findPrepared(Book::class,
            'SELECT BSL.book, Books.* FROM books_series_link BSL, books Books WHERE Books.id=BSL.book AND series=:id ORDER BY series_index',
            array('id' => $id));
        return array('series' => $series, 'books' => $books);
    }

    function seriesDetailsSlice($lang, $id, $index = 0, $length = 100, $filter)
    {
        $series = $this->findOne(Series::class, 'SELECT * FROM series WHERE id=:id', array('id' => $id));
        if (is_null($series))
            return array();
        $slice = $this->findSliceFiltered(CalibreSearchType::SeriesBook, $index, $length, $filter, NULL, $id);
        $this->addBookDetails($lang, $slice['entries']);
        return array('series' => $series) + $slice;
    }

    function seriesSlice($index = 0, $length = 100, $searchOptions = null): array
    {
        return $this->findSliceFiltered(CalibreSearchType::Series, $index, $length, new CalibreFilter(), $searchOptions, null);
    }

    function seriesInitials()
    {
        $initials = $this->findPrepared(Item::class,
            'SELECT DISTINCT substr(upper(name),1,1) AS initial FROM series ORDER BY initial ASC',
            array());
        $ret = array();
        foreach ($initials as $initial) {
            $i = new Item();
            $ctr = $this->findOne(Item::class, 'SELECT COUNT(*) as ctr FROM series WHERE substr(upper(name),1,1)=:initial', array('initial' => $initial->initial));
            $i->initial = $initial->initial;
            $i->ctr = $ctr->ctr;
            array_push($ret, $i);
        }
        return $ret;
    }

    function seriesNamesForInitial($initial)
    {
        if (strcasecmp($initial, "all") == 0) {
            $seriesNames = $this->findPrepared(Series::class, 'SELECT series.id, series.name, (SELECT COUNT(*) FROM books_series_link AS btl WHERE series.id = btl.series) AS anzahl FROM series ORDER BY series.name',
                array());
        } else {
            $seriesNames = $this->findPrepared(Series::class, 'SELECT series.id, series.name, (SELECT COUNT(*) FROM books_series_link AS btl WHERE series.id = btl.series) AS anzahl FROM series WHERE substr(upper(series.name),1,1)=:initial ORDER BY series.name',
                array('initial' => $initial));
        }
        return $seriesNames;
    }

    # Generate a list where the items are grouped and separated by
    # the initial character.
    # If the item has a 'sort' field that is used, else the name.
    function mkInitialedList($items)
    {
        $grouped_items = array();
        $initial_item = "";
        foreach ($items as $item) {
            if (isset($item->sort))
                $is = $item->sort;
            else
                $is = $item->name;
            $ix = mb_strtoupper(mb_substr($is, 0, 1, 'UTF-8'), 'UTF-8');
            if ($ix != $initial_item) {
                array_push($grouped_items, array('initial' => $ix));
                $initial_item = $ix;
            }
            array_push($grouped_items, $item);
        }
        return $grouped_items;
    }

    function kindleFormatSort($a, $b)
    {
        //global $kindleformats;
        $kindleformats[0] = "AZW3";
        $kindleformats[1] = "AZW";
        $kindleformats[3] = "MOBI";
        $kindleformats[4] = "HTML";
        $kindleformats[5] = "PDF";
        $sort = 0;
        foreach ($kindleformats as $key => $value) {
            if ($a->format == $value) {
                $sort = 0;
                break;
            } elseif ($b->format == $value) {
                $sort = 1;
                break;
            }
        }
        return $sort;
    }

    /**
     * @param int $id
     * @return object|null
     */
    public function series4Book(int $id): ?array
    {
        $series_ids = $this->findPrepared(BookSeriesLink::class, 'SELECT * FROM books_series_link WHERE book=:id',
            array('id' => $id));
        $series = array();
        foreach ($series_ids as $aid) {
            $this_series = $this->findOne(Series::class, 'SELECT * FROM series WHERE id=:id', array('id' => $aid->series));
            array_push($series, $this_series);
        }
        return $series;
    }
}
