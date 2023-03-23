<?php

namespace App\Domain\Calibre;

interface CalibreRepository
{
    /**
     * Is the Calibre library open?
     * @return boolean    true if open, else false
     */
    public function libraryOk(): bool;

    /**
     * Return the modification time of Calibre's metadata.db as Unix epoch.
     * @return int
     */
    public function getModTime(): int;

    /**
     * Return an array with library statistics for titles, authors etc.
     *
     * @param object $filter a QueryFilter
     * @return array            array of numbers fir titles, authers etc.
     */
    public function libraryStats(object $filter): array;

    /**
     * Return the number (int) of rows for a SQL COUNT Statement, e.g.
     * SELECT COUNT(*) FROM books;
     *
     * @param string $sql sql query
     * @param array $params query parameters
     * @return int                    number of result rows
     */
    public function count(string $sql, array $params): int;

    /**
     * Return the ID for a language code from the Calibre languages table
     * @param string $languageCode ISO 639-2 code, e.g. 'deu', 'eng'
     * @return          ?integer         ID or null
     */
    public function getLanguageId($languageCode);

    /**
     * Return the ID for a tag  from the Calibre tags table
     * @param string $tagName textual tag name
     * @return integer ID (>=1)  or 0 (not found)
     */
    public function getTagId(string $tagName): int;

    /**
     * Return the most recent books, sorted by modification date.
     * @param string $lang target language code
     * @param int $nrOfTitles number of titles, page size. Default is 30.
     * @param object $filter CalibreFilter
     * @return array of books
     * @deprecated
     */
    public function last30Books($lang, $nrOfTitles = 30, $filter = null);

    /**
     * Return just the pure author information.
     * @param integer $id Calibre ID for author
     * @return object    Calibre author record
     */
    public function author($id);

    /**
     * Find a single author and return the details plus all books.
     * @param integer $id author id
     * @return ?array            array with elements: author data, books
     */
    public function authorDetails($id);

    /**
     * Find a single author and return the details plus some books.
     *
     * @param string $lang target language code
     * @param integer $id author id
     * @param integer $index page index
     * @param integer $length page length
     * @param object $filter CalibreFilter
     * @return ?array           array with elements: author data, current page,
     *                               no. of pages, $length entries
     */
    public function authorDetailsSlice($lang, $id, $index = 0, $length = 100, $filter = null);

    /**
     * Search a list of authors defined by the parameters $index and $length.
     *
     * @param integer $index page index
     * @param integer $length page length
     * @param SearchOptions|null $searchOptions
     * @return array        with elements: current page,
     *                      no. of pages, $length entries
     */
    public function authorsSlice(int $index = 0, int $length = 100, SearchOptions $searchOptions=null): array;

    /**
     * Calc the position of the first name with initial $jumpTarget
     * @param string $jumpTarget title initial
     * @param SearchOptions $searchOptions restricts the search space
     * @return array position of first matching record (0 if not found), and total number
     */
    public function authorsCalcNamePos(string $jumpTarget, SearchOptions $searchOptions): array;

    /**
     * Find the initials of all author names and their frequency
     * @param SearchOptions $searchOptions
     * @return array an array of initials (initial) and corresponding frequency counter (ctr)
     */
    public function authorsInitials(SearchOptions $searchOptions): array;

    /**
     * Find all authors with a given initial and return their names and book count
     * @param string $initial initial character of last name, uppercase
     * @return array           array of authors with book count
     */
    public function authorsNamesForInitial($initial);

    /**
     * Gets a unique list of all series from the author.
     * @param integer $id author id
     * @param array $books array of all books from the author
     */
    public function authorSeries($id, $books);

    /**
     * Find all ID types in the Calibre identifiers table
     * @return array id type names
     */
    public function idTypes();

    /**
     * Return a list of all languages
     */
    public function languages();

    /**
     * Return a list of all tags, ordered by name
     */
    public function tags();

    /**
     * Returns a tag and the related books
     * @param integer $id tag id
     * @return ?array            array with elements: tag data, books
     */
    public function tagDetails($id);

    /**
     * Find a single tag and return the details plus some books.
     *
     * @param string $lang target language code
     * @param integer $id tagid
     * @param integer $index page index
     * @param integer $length page length
     * @param object $filter CalibreFilter
     * @return array           array with elements: tag data, current page,
     *                               no. of pages, $length entries
     */
    public function tagDetailsSlice(string $lang, int $id, $index = 0, $length = 100, $filter = null): array;

    /**
     * Search a list of tags defined by the parameters $index and $length.
     * @param int $index
     * @param int $length
     * @param SearchOptions|null $searchOptions
     * @return array of tags matching
     */
    public function tagsSlice($index = 0, $length = 100, SearchOptions $searchOptions=null): array;

    /**
     * Calc the position of the first name with initial $jumpTarget
     * @param string $jumpTarget title initial
     * @param SearchOptions $searchOptions restricts the search space
     * @return array position of first matching record (0 if not found), and total number
     */
    public function tagsCalcNamePos(string $jumpTarget, SearchOptions $searchOptions): array;

    /**
     * Find the initials of all tags and their frequencies
     * @return array an array of Items with initial character and tag count
     */
    public function tagsInitials(SearchOptions $searchOptions);

    /**
     * Find all authors with a given initial and return their names and book count
     * @param string $initial initial character of last name, uppercase
     * @return array           array of authors with book count
     */
    public function tagsNamesForInitial($initial);

    /**
     * Search a list of books in publication date order, defined by the parameters $index and $length.
     * If $search is defined it is used to filter the book title, ignoring case.
     * @param string $lang target language code
     * @param integer $index page index, default 0
     * @param integer $length page length, default 100
     * @param object $filter CalibreFilter
     * @param string $search search phrase, default null
     * @return  array               an array with elements: current page, no. of pages, $length entries
     */
    public function pubdateOrderedTitlesSlice($lang, $index = 0, $length = 100, $filter = null, $search = null);

    /**
     * Search a list of books in last modified order, defined by the parameters $index and $length.
     * If $search is defined it is used to filter the book title, ignoring case.
     * @param string $lang target language code
     * @param integer $index page index, default 0
     * @param integer $length page length, default 100
     * @param object $filter CalibreFilter
     * @param string $search search phrase, default null
     * @return  array               an array with elements: current page, no. of pages, $length entries
     */
    public function lastmodifiedOrderedTitlesSlice($lang, $index = 0, $length = 100, $filter = null, $search = null);

    /**
     * Search a list of books in timestamp order, defined by the parameters $index and $length.
     * If $search is defined it is used to filter the book title, ignoring case.
     * @param string $lang target language code
     * @param integer $index page index, default 0
     * @param integer $length page length, default 100
     * @param object $filter CalibreFilter
     * @param string $search search phrase, default null
     * @return  array               an array with elements: current page, no. of pages, $length entries
     */
    public function timestampOrderedTitlesSlice($lang, $index = 0, $length = 100, $filter = null, $search = null);

    /**
     * Search a list of books defined by the parameters $index and $length.

     * @param string $lang target language code
     * @param int $index page index, default 0
     * @param int $length page length, default 100
     * @param CalibreFilter $filter CalibreFilter
     * @param SearchOptions|null $searchOptions
     * @return  array          an array with elements: current page, no. of pages, $length entries
     */
    public function titlesSlice(string $lang, int $index, int $length, CalibreFilter $filter, SearchOptions $searchOptions=null): array;

    /**
     * Find only one book
     * @param int $id
     * @return object
     * @throws TitleNotFoundException
     */
    public function title(int $id): object;

    /**
     * Returns the path to the cover image of a book
     * @param int $id
     * @return string
     * @throws TitleNotFoundException if the book wasn't found
     * @throws CoverNotFoundException if the cover file wasn't found
     */
    public function titleCover(int $id): string;

    /**
     * Try to find the language of a book. Returns an emty string, if there is none.
     * @param int $book_id the Calibre book ID
     * @return string                the language string or an empty string
     **/
    public function getLanguage($book_id);

    /**
     * Try to find the languages of a book. Returns an empty array, if there is none.
     * @param int $book_id the Calibre book ID
     * @return array                the language strings
     **/
    public function getLanguages($book_id);

    /**
     * Find a single book plus all kinds of details.
     * @param string $lang the user's language code
     * @param int $id the Calibre book ID
     * @return array            the book, its authors, series, tags, formats, languages, ids and comment.
     * @throws TitleNotFoundException if the is is not found
     */
    public function titleDetails($lang, $id): array;

    /**
     * Find a single book, its tags and languages. Mainly used for restriction checks.
     * @param int $id the Calibre book ID
     * @return ?array            the book, its tags and languages
     */
    public function titleDetailsMini($id);

    /**
     * Find the custom colums for a book.
     * Composite columns are ignored, because there are (currently?) no values in
     * the db tables.
     * @param integer $book_id ID of the book
     * @return array                an array of arrays. one entry for each custom column
     *                                with name, type and value
     */
    public function customColumns($book_id);

    /**
     * Find a subset of the details for a book that is sufficient for an OPDS
     * partial acquisition feed. The function assumes that the book record has
     * already been loaded.
     * @param Book $book complete book record from title()
     * @return ?array        the book and its authors, tags and formats
     */
    public function titleDetailsOpds($book);

    /**
     * Retrieve the OPDS title details for a collection of Books and
     * filter out the titles without a downloadable format.
     *
     * This is a utilty function for OPDS, because OPDS acquisition feeds don't
     * valdate if there are entries without acquisition links to downloadable files.
     *
     * @param array $books a collection of Book instances
     * @return array         the book and its authors, tags and formats
     */
    public function titleDetailsFilteredOpds($books);

    /**
     * Returns the path to the file of a book or NULL.
     * @param int $id book id
     * @param string $file file name
     * @return ?string       full path to image file or NULL
     */
    public function titleFile($id, $file);

    /**
     * Returns the path to the file of a book or NULL.
     * @param int $id book id
     * @param string $format format name
     * @return ?string       full path to image file or NULL
     */
    public function titleFileByFormat($id, $format);

    /**
     * Return the formats for a book
     * @param int $bookid Calibre book id
     * @return array                the formats for the book
     */
    public function titleGetFormats($bookid);

    /**
     * Returns a Kindle supported format of a book or NULL.
     * We always return the best of the available formats supported by Kindle devices
     * E.g. when there is both a Mobi and a PDF file for a given book, we always return the Mobi
     * @param int $id book id
     * @return ?object   $format    the kindle format object for the book or NULL
     */
    public function titleGetKindleFormat($id);

    /**
     * Find the initials of titles and their frequency
     * @param SearchOptions $searchOptions restrict the titles processed
     * @return array an array of initials (initial) and corresponding frequency counter (ctr)
     */
    public function titlesInitials(SearchOptions $searchOptions): array;

    /**
     * Find the pub/mod/etc years of titles and their frequency
     * @param SearchOptions $searchOptions restrict the titles processed
     * @param string $timeSortOption defines what field will be used for search
     * @return array an array of years and corresponding frequency counter (ctr)
     */
    public function titlesYears(SearchOptions $searchOptions, string $timeSortOption): array;

    /**
     * Calc the position of the first title/name with initial $jumpTarget
     * @param string $jumpTarget title initial
     * @param SearchOptions $searchOptions
     * @return array position of first matching record (0 if not found), and total number
     */
    public function titlesCalcTitlePos(string $jumpTarget, SearchOptions $searchOptions): array;

    /**
     * Calc the position of the first title with year $jumpTarget
     * @param string $jumpTarget title year
     * @param SearchOptions $searchOptions
     * @param string $sort
     * @return array position of first matching record (0 if not found), and total number
     */
    public function titlesCalcYearPos(string $jumpTarget, SearchOptions $searchOptions, string $sort): array;

    /**
     * Find a single series and return the details plus all books.
     * @param int $id series id
     * @return ?array  an array with series details (key 'series') and
     *                the related books (key 'books')
     * @deprecated since 0.9.3
     */
    public function seriesDetails($id);

    /**
     * Find a single series and return the details plus some books.
     *
     * @param string $lang target language code
     * @param integer $id series id
     * @param integer $index page index
     * @param integer $length page length
     * @param object $filter CalibreFilter
     * @return array           array with elements: series data, current page,
     *                               no. of pages, $length entries
     */
    public function seriesDetailsSlice($lang, $id, $index = 0, $length = 100, $filter = null);

    /**
     * Find series info for a book
     * @param int $id book id
     * @return array|null series or null if no series available
     */
    public function series4Book(int $id): ?array;

    /**
     * Search a list of series defined by the parameters $index and $length.
     *
     * @param integer $index =0     page indes
     * @param integer $length =100  page length
     * @param SearchOptions|null $searchOptions
     * @return array                see findSlice
     */
    public function seriesSlice($index = 0, $length = 100, SearchOptions $searchOptions=null): array;

    /**
     * Calc the position of the first name with initial $jumpTarget
     * @param string $jumpTarget title initial
     * @param SearchOptions $searchOptions restricts the search space
     * @return array position of first matching record (0 if not found), and total number
     */
    public function seriesCalcNamePos(string $jumpTarget, SearchOptions $searchOptions): array;

    /**
     * Find the initials of all series and their frequencies
     * @return array an array of Items with initial character and series count
     */
    public function seriesInitials(SearchOptions $searchOptions): array;

    /**
     * Find all series with a given initial and return their names and book count
     * @param string $initial initial character of name, uppercase
     * @return array           array of Series with book count
     */
    public function seriesNamesForInitial($initial);

    public function mkInitialedList($items);

    /**
     * Usort helper function
     * sorts the formats array-of-objects by priority set by kindleformats array
     * @param object $a
     * @param object $b
     * @return int
     */
    public function kindleFormatSort($a, $b);
}
