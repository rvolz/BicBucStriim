<?php

namespace Tests\Domain\Calibre;

use App\Domain\Calibre\Book;
use App\Domain\Calibre\Calibre;
use App\Domain\Calibre\CalibreFilter;
use App\Domain\Calibre\TitleNotFoundException;
use PHPUnit\Framework\TestCase;

class CalibreTest extends TestCase
{
    const CDB1 = __DIR__ . '/../../fixtures/metadata_empty.db';
    const CDB2 = __DIR__ . '/../../fixtures/lib2/metadata.db';
    const CDB3 = __DIR__ . '/../../fixtures/lib3/metadata.db';

    var Calibre $calibre;

    function setUp(): void
    {
        $this->calibre = new Calibre(self::CDB2);
    }

    function tearDown(): void
    {
        //$this->calibre = (Calibre) null;
    }

    function testOpenCalibreEmptyDb()
    {
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertTrue($this->calibre->libraryOk());
    }

    function testOpenCalibreNotExistingDb()
    {
        $this->calibre = new Calibre(self::CDB3);
        $this->assertFalse($this->calibre->libraryOk());
        $this->assertEquals(0, $this->calibre->last_error);
    }

    function testGetTagId() {
        $this->assertEquals(21, $this->calibre->getTagId('Architecture'));
        // Note: Changed!
        //$this->assertNull($this->calibre->getTagId('Nothing'));
        $this->assertEquals(0, $this->calibre->getTagId('Nothing'));
    }

    function testGetLanguageId() {
        $this->assertEquals(3, $this->calibre->getLanguageId('eng'));
        $this->assertNull($this->calibre->getLanguageId('Nothing'));
    }

    function testLibraryStatsEmptyFilter()
    {
        $result = $this->calibre->libraryStats(new CalibreFilter());
        $this->assertEquals(7, $result["titles"]);
        $this->assertEquals(6, $result["authors"]);
        $this->assertEquals(6, $result["tags"]);
        $this->assertEquals(3, $result["series"]);
    }

    function testLibraryStatsLanguageFilter()
    {
        $result = $this->calibre->libraryStats(new CalibreFilter($lang=3,$tag=null));
        $this->assertEquals(1, $result["titles"]);
        $this->assertEquals(6, $result["authors"]);
        $this->assertEquals(6, $result["tags"]);
        $this->assertEquals(3, $result["series"]);
    }

    function testLibraryStatsLanguageAndTagFilter()
    {
        $result = $this->calibre->libraryStats(new CalibreFilter($lang=1,$tag=3));
        $this->assertEquals(1, $result["titles"]);
        $this->assertEquals(6, $result["authors"]);
        $this->assertEquals(6, $result["tags"]);
        $this->assertEquals(3, $result["series"]);
    }

    function testLast30()
    {
        $result = $this->calibre->last30Books('en', 30, new CalibreFilter());
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertFalse($result === false);
        $this->assertEquals(7, count($result));
        $result2 = $this->calibre->last30Books('en', 2, new CalibreFilter());
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertFalse($result2 === false);
        $this->assertEquals(2, count($result2));
        $result3 = $this->calibre->last30Books('en', 30, new CalibreFilter($lang = 3));
        $this->assertEquals(1, count($result3));
        $result4 = $this->calibre->last30Books('en', 30, new CalibreFilter($lang = null, $tag = 21));
        $this->assertEquals(6, count($result4));
        $result5 = $this->calibre->last30Books('en', 30, new CalibreFilter($lang = 3, $tag = 21));
        $this->assertEquals(0, count($result5));
        $result3 = $this->calibre->last30Books('en', 30, new CalibreFilter($lang = 2));
        $this->assertEquals(2, count($result3));
        $result4 = $this->calibre->last30Books('en', 30, new CalibreFilter($lang = 2, $tag = 3));
        $this->assertEquals(1, count($result4));
    }

    function testAuthorsSlice()
    {
        $result0 = $this->calibre->authorsSlice(0, 2);
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(3, $result0['pages']);
        $result1 = $this->calibre->authorsSlice(1, 2);
        $this->assertEquals(2, count($result1['entries']));
        $this->assertEquals(1, $result1['page']);
        $this->assertEquals(3, $result1['pages']);
        $result2 = $this->calibre->authorsSlice(2, 2);
        $this->assertEquals(2, count($result2['entries']));
        $this->assertEquals(2, $result2['page']);
        $this->assertEquals(3, $result2['pages']);
        $no_result = $this->calibre->authorsSlice(100, 2);
        $this->assertEquals(0, count($no_result['entries']));
        $this->assertEquals(100, $no_result['page']);
        $this->assertEquals(3, $no_result['pages']);
    }

    function testAuthorsSliceSearch()
    {
        $result0 = $this->calibre->authorsSlice(0, 2, 'I');
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(3, $result0['pages']);
        $result1 = $this->calibre->authorsSlice(1, 2, 'I');
        $this->assertEquals(2, count($result1['entries']));
        $result3 = $this->calibre->authorsSlice(2, 2, 'I');
        $this->assertEquals(1, count($result3['entries']));
    }

    function testAuthorDetailsSlice()
    {
        $result0 = $this->calibre->authorDetailsSlice('en', 6, 0, 1, new CalibreFilter());
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(1, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(2, $result0['pages']);
        $result1 = $this->calibre->authorDetailsSlice('en', 6, 1, 1, new CalibreFilter());
        $this->assertEquals(1, count($result1['entries']));
        $this->assertEquals(1, $result1['page']);
        $this->assertEquals(2, $result1['pages']);
    }

    function testAuthorDetailsSliceWithFilter()
    {
        $result0 = $this->calibre->authorDetailsSlice('en', 7, 0, 1, new CalibreFilter());
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(1, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(1, $result0['pages']);
        $result1 = $this->calibre->authorDetailsSlice('en', 7, 0, 1, new CalibreFilter(1));
        $this->assertEquals(1, count($result1['entries']));
        $this->assertEquals(0, $result1['page']);
        $this->assertEquals(1, $result1['pages']);
        $result2 = $this->calibre->authorDetailsSlice('en', 7, 0, 1, new CalibreFilter(2));
        $this->assertEquals(0, count($result2['entries']));
        $this->assertEquals(0, $result2['page']);
        $this->assertEquals(0, $result2['pages']);
    }

    function testAuthorsInitials()
    {
        $result = $this->calibre->authorsInitials();
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(5, count($result));
        $this->assertEquals('E', $result[0]->initial);
        $this->assertEquals(1, $result[0]->ctr);
        $this->assertEquals('R', $result[4]->initial);
        $this->assertEquals(2, $result[4]->ctr);
    }

    function testAuthorsNamesForInitial()
    {
        $result = $this->calibre->authorsNamesForInitial('R');
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result));
        $this->assertEquals(1, $result[0]->anzahl);
        $this->assertEquals('Rilke, Rainer Maria', $result[0]->sort);
    }

    function testTagsSlice()
    {
        $result0 = $this->calibre->tagsSlice(0, 2);
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(3, $result0['pages']);
        $result1 = $this->calibre->tagsSlice(1, 2);
        $this->assertEquals(2, count($result1['entries']));
        $this->assertEquals(1, $result1['page']);
        $this->assertEquals(3, $result1['pages']);
        $result2 = $this->calibre->tagsSlice(2, 2);
        $this->assertEquals(2, count($result2['entries']));
        $this->assertEquals(2, $result2['page']);
        $this->assertEquals(3, $result2['pages']);
        $no_result = $this->calibre->tagsSlice(100, 2);
        $this->assertEquals(0, count($no_result['entries']));
        $this->assertEquals(100, $no_result['page']);
        $this->assertEquals(3, $no_result['pages']);
    }

    function testTagsSliceSearch()
    {
        $result0 = $this->calibre->tagsSlice(0, 2, 'I');
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(3, $result0['pages']);
        $result1 = $this->calibre->tagsSlice(1, 2, 'I');
        $this->assertEquals(2, count($result1['entries']));
        $result3 = $this->calibre->tagsSlice(2, 2, 'I');
        $this->assertEquals(1, count($result3['entries']));
    }

    function testTagDetailsSlice()
    {
        $result0 = $this->calibre->tagDetailsSlice('en', 3, 0, 1, new CalibreFilter());
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(1, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(2, $result0['pages']);
        $result1 = $this->calibre->tagDetailsSlice('en', 3, 1, 1, new CalibreFilter());
        $this->assertEquals(1, count($result1['entries']));
        $this->assertEquals(1, $result1['page']);
        $this->assertEquals(2, $result1['pages']);
    }

    function testTagsInitials()
    {
        $result = $this->calibre->tagsInitials();
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(5, count($result));
        $this->assertEquals('A', $result[0]->initial);
        $this->assertEquals(1, $result[0]->ctr);
        $this->assertEquals('V', $result[4]->initial);
        $this->assertEquals(1, $result[4]->ctr);
    }

    function testTagsNamesForInitial()
    {
        $result = $this->calibre->tagsNamesForInitial('B');
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result));
        $this->assertEquals(1, $result[0]->anzahl);
        $this->assertEquals('Belletristik & Literatur', $result[0]->name);
    }

    function testTimestampOrderedTitlesSlice()
    {
        $result0 = $this->calibre->timestampOrderedTitlesSlice('en', 0, 2, new CalibreFilter());
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(4, $result0['pages']);
        $this->assertEquals(7, $result0['entries'][0]->id);
        $this->assertEquals(6, $result0['entries'][1]->id);
        $result1 = $this->calibre->timestampOrderedTitlesSlice('en', 1, 2, new CalibreFilter());
        $this->assertEquals(2, count($result1['entries']));
        $this->assertEquals(1, $result1['page']);
        $this->assertEquals(4, $result1['pages']);
        $this->assertEquals(5, $result1['entries'][0]->id);
        $this->assertEquals(4, $result1['entries'][1]->id);
        $result3 = $this->calibre->timestampOrderedTitlesSlice('en', 3, 2, new CalibreFilter());
        $this->assertEquals(1, count($result3['entries']));
        $this->assertEquals(3, $result3['page']);
        $this->assertEquals(4, $result3['pages']);
        $this->assertEquals(1, $result3['entries'][0]->id);
        $no_result = $this->calibre->timestampOrderedTitlesSlice('en', 100, 2, new CalibreFilter());
        $this->assertEquals(0, count($no_result['entries']));
        $this->assertEquals(100, $no_result['page']);
        $this->assertEquals(4, $no_result['pages']);

        $result0 = $this->calibre->timestampOrderedTitlesSlice('en', 0, 2, new CalibreFilter($lang = 3));
        $this->assertEquals(1, count($result0['entries']));
        $this->assertEquals(1, $result0['pages']);
        $result0 = $this->calibre->timestampOrderedTitlesSlice('en', 1, 2, new CalibreFilter($lang = null, $tag = 21));
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(1, $result0['page']);
        $this->assertEquals(3, $result0['pages']);
        $result0 = $this->calibre->timestampOrderedTitlesSlice('en', 0, 2, new CalibreFilter($lang = 3, $tag = 21));
        $this->assertEquals(0, count($result0['entries']));
        $this->assertEquals(0, $result0['pages']);
        $result0 = $this->calibre->timestampOrderedTitlesSlice('en', 0, 2, new CalibreFilter($lang = 2, $tag = 3));
        $this->assertEquals(1, count($result0['entries']));
        $this->assertEquals(1, $result0['pages']);
    }

    function testPubdateOrderedTitlesSlice()
    {
        $result0 = $this->calibre->pubdateOrderedTitlesSlice('en', 0, 2, new CalibreFilter());
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(4, $result0['pages']);
        $this->assertEquals(7, $result0['entries'][0]->id);
        $this->assertEquals(6, $result0['entries'][1]->id);
        $result1 = $this->calibre->pubdateOrderedTitlesSlice('en', 1, 2, new CalibreFilter());
        $this->assertEquals(2, count($result1['entries']));
        $this->assertEquals(1, $result1['page']);
        $this->assertEquals(4, $result1['pages']);
        $this->assertEquals(5, $result1['entries'][0]->id);
    }

    function testLastmodifiedOrderedTitlesSlice()
    {
        $result0 = $this->calibre->lastmodifiedOrderedTitlesSlice('en', 0, 2, new CalibreFilter());
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(4, $result0['pages']);
        $this->assertEquals(7, $result0['entries'][0]->id);
        $this->assertEquals(6, $result0['entries'][1]->id);
        $result1 = $this->calibre->lastmodifiedOrderedTitlesSlice('en', 1, 2, new CalibreFilter());
        $this->assertEquals(2, count($result1['entries']));
        $this->assertEquals(1, $result1['page']);
        $this->assertEquals(4, $result1['pages']);
        $this->assertEquals(5, $result1['entries'][0]->id);
        $this->assertEquals(1, $result1['entries'][1]->id);
    }

    function testTitlesSlice()
    {
        $result0 = $this->calibre->titlesSlice('en', 0, 2, new CalibreFilter());
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(4, $result0['pages']);
        $result1 = $this->calibre->titlesSlice('en', 1, 2, new CalibreFilter());
        $this->assertEquals(2, count($result1['entries']));
        $this->assertEquals(1, $result1['page']);
        $this->assertEquals(4, $result1['pages']);
        $result3 = $this->calibre->titlesSlice('en', 3, 2, new CalibreFilter());
        $this->assertEquals(1, count($result3['entries']));
        $this->assertEquals(3, $result3['page']);
        $this->assertEquals(4, $result3['pages']);
        $no_result = $this->calibre->titlesSlice('en', 100, 2, new CalibreFilter());
        $this->assertEquals(0, count($no_result['entries']));
        $this->assertEquals(100, $no_result['page']);
        $this->assertEquals(4, $no_result['pages']);

        $result0 = $this->calibre->titlesSlice('en', 0, 2, new CalibreFilter($lang = 3));
        $this->assertEquals(1, count($result0['entries']));
        $this->assertEquals(1, $result0['pages']);
        $result0 = $this->calibre->titlesSlice('en', 1, 2, new CalibreFilter($lang = null, $tag = 21));
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(1, $result0['page']);
        $this->assertEquals(3, $result0['pages']);
        $result0 = $this->calibre->titlesSlice('en', 0, 2, new CalibreFilter($lang = 3, $tag = 21));
        $this->assertEquals(0, count($result0['entries']));
        $this->assertEquals(0, $result0['pages']);
        $result0 = $this->calibre->titlesSlice('en', 0, 2, new CalibreFilter($lang = 2, $tag = 3));
        $this->assertEquals(1, count($result0['entries']));
        $this->assertEquals(1, $result0['pages']);
    }

    function testCount()
    {
        $count = 'select count(*) from books where lower(title) like :search';
        $params = array('search' => '%i%');
        $result = $this->calibre->count($count, $params);
        $this->assertEquals(6, $result);
    }

    function testTitlesSliceSearch()
    {
        $result0 = $this->calibre->titlesSlice('en', 0, 2, new CalibreFilter(), 'I');
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(3, $result0['pages']);
        $result1 = $this->calibre->titlesSlice('en', 1, 2, new CalibreFilter(), 'I');
        $this->assertEquals(2, count($result1['entries']));
        $result3 = $this->calibre->titlesSlice('en', 2, 2, new CalibreFilter(), 'I');
        $this->assertEquals(2, count($result3['entries']));
    }

    function testAuthorDetails()
    {
        $result = $this->calibre->authorDetails(7);
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertNotNull($result);
        $this->assertEquals('Lessing, Gotthold Ephraim', $result['author']->sort);
    }

    function testTagDetails()
    {
        $result = $this->calibre->tagDetails(3);
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertNotNull($result);
        $this->assertEquals('Fachbücher', $result['tag']->name);
        $this->assertEquals(2, count($result['books']));
    }

    function testTitle()
    {
        $result = $this->calibre->title(3);
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertInstanceOf(Book::class, $result);
        $this->assertEquals('Der seltzame Springinsfeld', $result->title);

        $this->expectException(TitleNotFoundException::class);
        $this->calibre->title(99999);
    }

    function testTitleCover()
    {
        $result = $this->calibre->titleCover(3);
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertNotNull($result);
        $this->assertEquals('cover.jpg', basename($result));
    }

    function testTitleFile()
    {
        $result = $this->calibre->titleFile(3, 'Der seltzame Springinsfeld - Hans Jakob Christoffel von Grimmelshausen.epub');
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertNotNull($result);
        $this->assertEquals('Der seltzame Springinsfeld - Hans Jakob Christoffel von Grimmelshausen.epub', basename($result));
    }

    function testTitleDetails()
    {
        $result = $this->calibre->titleDetails('en', 3);
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertFalse($result === false);
        $this->assertEquals('Der seltzame Springinsfeld', $result['book']->title);
        $this->assertEquals('Fachbücher', $result['tags'][0]->name);
        $this->assertEquals('Serie Grimmelshausen', $result['series'][0]->name);
    }

    function testTitleDetailsOpds()
    {
        $book = $this->calibre->title(3);
        $result = $this->calibre->titleDetailsOpds($book);
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertFalse($result === false);
        $this->assertEquals('Der seltzame Springinsfeld', $result['book']->title);
        $this->assertEquals('Fachbücher', $result['tags'][0]->name);
    }

    function testTitleDetailsFilteredOpds()
    {
        $books = $this->calibre->titlesSlice('en', 1, 2, new CalibreFilter());
        $this->assertEquals(2, count($books['entries']));
        $result = $this->calibre->titleDetailsFilteredOpds($books['entries']);
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertFalse($result === false);
        $this->assertEquals(1, count($result));
    }

    function testSeriesSlice()
    {
        $result0 = $this->calibre->seriesSlice(0, 2);
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(2, $result0['pages']);
        $result1 = $this->calibre->seriesSlice(1, 2);
        $this->assertEquals(1, count($result1['entries']));
        $this->assertEquals(1, $result1['page']);
        $this->assertEquals(2, $result1['pages']);
    }

    function testSeriesSliceSearch()
    {
        $result0 = $this->calibre->seriesSlice(0, 2, 'I');
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(2, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(2, $result0['pages']);
        $result1 = $this->calibre->seriesSlice(1, 2, 'I');
        $this->assertEquals(1, count($result1['entries']));
    }

    function testSeriesDetailsSlice()
    {
        $result0 = $this->calibre->seriesDetailsSlice('en', 1, 0, 1, new CalibreFilter());
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(1, count($result0['entries']));
        $this->assertEquals(0, $result0['page']);
        $this->assertEquals(2, $result0['pages']);
        $result1 = $this->calibre->seriesDetailsSlice('en', 1, 1, 1, new CalibreFilter());
        $this->assertEquals(1, count($result1['entries']));
        $this->assertEquals(1, $result1['page']);
        $this->assertEquals(2, $result1['pages']);
    }

    function testSeriesDetails()
    {
        $result = $this->calibre->seriesDetails(5);
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertNotNull($result);
        $this->assertEquals('Serie Rilke', $result['series']->name);
        $this->assertEquals(1, count($result['books']));
    }

    function testSeriesInitials()
    {
        $result = $this->calibre->seriesInitials();
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(1, count($result));
        $this->assertEquals('S', $result[0]->initial);
        $this->assertEquals(3, $result[0]->ctr);
    }

    function testSeriesNamesForInitial()
    {
        $result = $this->calibre->seriesNamesForInitial('S');
        $this->assertEquals(0, $this->calibre->last_error);
        $this->assertEquals(3, count($result));
        $this->assertEquals(2, $result[0]->anzahl);
        $this->assertEquals('Serie Grimmelshausen', $result[0]->name);
    }

}
