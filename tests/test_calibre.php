<?php
set_include_path("tests:vendor");
require_once('simpletest/simpletest/autorun.php');
require_once('lib/BicBucStriim/calibre.php');
require_once('lib/BicBucStriim/calibre_filter.php');

class TestOfCalibre extends UnitTestCase
{

    const CDB1 = './tests/fixtures/metadata_empty.db';
    const CDB2 = './tests/fixtures/lib2/metadata.db';
    const CDB3 = './tests/fixtures/lib3/metadata.db';

    var $calibre;

    function setUp()
    {
        $this->calibre = new Calibre(self::CDB2);
    }

    function tearDown()
    {
        $this->calibre = NULL;
    }

    function testOpenCalibreEmptyDb()
    {
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertTrue($this->calibre->libraryOk());
    }

    function testOpenCalibreNotExistingDb()
    {
        $this->calibre = new Calibre(self::CDB3);
        $this->assertFalse($this->calibre->libraryOk());
        $this->assertEqual(0, $this->calibre->last_error);
    }

    function testGetTagId() {
        $this->assertEqual(21, $this->calibre->getTagId('Architecture'));
        $this->assertNull($this->calibre->getTagId('Nothing'));
    }

    function testGetLanguageId() {
        $this->assertEqual(3, $this->calibre->getLanguageId('eng'));
        $this->assertNull($this->calibre->getLanguageId('Nothing'));
    }

    function testLibraryStatsEmptyFilter()
    {
        $result = $this->calibre->libraryStats(new CalibreFilter());
        $this->assertEqual(7, $result["titles"]);
        $this->assertEqual(6, $result["authors"]);
        $this->assertEqual(6, $result["tags"]);
        $this->assertEqual(3, $result["series"]);
    }

    function testLibraryStatsTagFilter()
    {
        $result = $this->calibre->libraryStats(new CalibreFilter($lang=null,$tag=21));
        $this->assertEqual(6, $result["titles"]);
        $this->assertEqual(6, $result["authors"]);
        $this->assertEqual(6, $result["tags"]);
        $this->assertEqual(3, $result["series"]);
    }

    function testLibraryStatsLanguageFilter()
    {
        $result = $this->calibre->libraryStats(new CalibreFilter($lang=3,$tag=null));
        $this->assertEqual(1, $result["titles"]);
        $this->assertEqual(6, $result["authors"]);
        $this->assertEqual(6, $result["tags"]);
        $this->assertEqual(3, $result["series"]);
    }

    function testLibraryStatsLanguageAndTagFilter()
    {
        $result = $this->calibre->libraryStats(new CalibreFilter($lang=1,$tag=3));
        $this->assertEqual(1, $result["titles"]);
        $this->assertEqual(6, $result["authors"]);
        $this->assertEqual(6, $result["tags"]);
        $this->assertEqual(3, $result["series"]);
    }

    function testLast30()
    {
        $result = $this->calibre->last30Books('en', 30, new CalibreFilter());
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertFalse($result === FALSE);
        $this->assertEqual(7, count($result));
        $result2 = $this->calibre->last30Books('en', 2, new CalibreFilter());
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertFalse($result2 === FALSE);
        $this->assertEqual(2, count($result2));
        $result3 = $this->calibre->last30Books('en', 30, new CalibreFilter($lang = 3));
        $this->assertEqual(1, count($result3));
        $result4 = $this->calibre->last30Books('en', 30, new CalibreFilter($lang = null, $tag = 21));
        $this->assertEqual(6, count($result4));
        $result5 = $this->calibre->last30Books('en', 30, new CalibreFilter($lang = 3, $tag = 21));
        $this->assertEqual(0, count($result5));
        $result3 = $this->calibre->last30Books('en', 30, new CalibreFilter($lang = 2));
        $this->assertEqual(2, count($result3));
        $result4 = $this->calibre->last30Books('en', 30, new CalibreFilter($lang = 2, $tag = 3));
        $this->assertEqual(1, count($result4));
    }

    function testAuthorsSlice()
    {
        $result0 = $this->calibre->authorsSlice(0, 2);
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertEqual(2, count($result0['entries']));
        $this->assertEqual(0, $result0['page']);
        $this->assertEqual(3, $result0['pages']);
        $result1 = $this->calibre->authorsSlice(1, 2);
        $this->assertEqual(2, count($result1['entries']));
        $this->assertEqual(1, $result1['page']);
        $this->assertEqual(3, $result1['pages']);
        $result2 = $this->calibre->authorsSlice(2, 2);
        $this->assertEqual(2, count($result2['entries']));
        $this->assertEqual(2, $result2['page']);
        $this->assertEqual(3, $result2['pages']);
        $no_result = $this->calibre->authorsSlice(100, 2);
        $this->assertEqual(0, count($no_result['entries']));
        $this->assertEqual(100, $no_result['page']);
        $this->assertEqual(3, $no_result['pages']);
    }

    function testAuthorsSliceSearch()
    {
        $result0 = $this->calibre->authorsSlice(0, 2, 'I');
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertEqual(2, count($result0['entries']));
        $this->assertEqual(0, $result0['page']);
        $this->assertEqual(3, $result0['pages']);
        $result1 = $this->calibre->authorsSlice(1, 2, 'I');
        $this->assertEqual(2, count($result1['entries']));
        $result3 = $this->calibre->authorsSlice(2, 2, 'I');
        $this->assertEqual(1, count($result3['entries']));
    }

    function testAuthorDetailsSlice()
    {
        $result0 = $this->calibre->authorDetailsSlice('en', 6, 0, 1, new CalibreFilter());
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertEqual(1, count($result0['entries']));
        $this->assertEqual(0, $result0['page']);
        $this->assertEqual(2, $result0['pages']);
        $result1 = $this->calibre->authorDetailsSlice('en', 6, 1, 1, new CalibreFilter());
        $this->assertEqual(1, count($result1['entries']));
        $this->assertEqual(1, $result1['page']);
        $this->assertEqual(2, $result1['pages']);
    }

    function testAuthorDetailsSliceWithFilter()
    {
        $result0 = $this->calibre->authorDetailsSlice('en', 7, 0, 1, new CalibreFilter());
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertEqual(1, count($result0['entries']));
        $this->assertEqual(0, $result0['page']);
        $this->assertEqual(1, $result0['pages']);
        $result1 = $this->calibre->authorDetailsSlice('en', 7, 0, 1, new CalibreFilter(1));
        $this->assertEqual(1, count($result1['entries']));
        $this->assertEqual(0, $result1['page']);
        $this->assertEqual(1, $result1['pages']);
        $result2 = $this->calibre->authorDetailsSlice('en', 7, 0, 1, new CalibreFilter(2));
        $this->assertEqual(0, count($result2['entries']));
        $this->assertEqual(0, $result2['page']);
        $this->assertEqual(0, $result2['pages']);
    }

    function testAuthorsInitials()
    {
        $result = $this->calibre->authorsInitials();
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertEqual(5, count($result));
        $this->assertEqual('E', $result[0]->initial);
        $this->assertEqual(1, $result[0]->ctr);
        $this->assertEqual('R', $result[4]->initial);
        $this->assertEqual(2, $result[4]->ctr);
    }

    function testAuthorsNamesForInitial()
    {
        $result = $this->calibre->authorsNamesForInitial('R');
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertEqual(2, count($result));
        $this->assertEqual(1, $result[0]->anzahl);
        $this->assertEqual('Rilke, Rainer Maria', $result[0]->sort);
    }

    function testTagsSlice()
    {
        $result0 = $this->calibre->tagsSlice(0, 2);
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertEqual(2, count($result0['entries']));
        $this->assertEqual(0, $result0['page']);
        $this->assertEqual(3, $result0['pages']);
        $result1 = $this->calibre->tagsSlice(1, 2);
        $this->assertEqual(2, count($result1['entries']));
        $this->assertEqual(1, $result1['page']);
        $this->assertEqual(3, $result1['pages']);
        $result2 = $this->calibre->tagsSlice(2, 2);
        $this->assertEqual(2, count($result2['entries']));
        $this->assertEqual(2, $result2['page']);
        $this->assertEqual(3, $result2['pages']);
        $no_result = $this->calibre->tagsSlice(100, 2);
        $this->assertEqual(0, count($no_result['entries']));
        $this->assertEqual(100, $no_result['page']);
        $this->assertEqual(3, $no_result['pages']);
    }

    function testTagsSliceSearch()
    {
        $result0 = $this->calibre->tagsSlice(0, 2, 'I');
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertEqual(2, count($result0['entries']));
        $this->assertEqual(0, $result0['page']);
        $this->assertEqual(3, $result0['pages']);
        $result1 = $this->calibre->tagsSlice(1, 2, 'I');
        $this->assertEqual(2, count($result1['entries']));
        $result3 = $this->calibre->tagsSlice(2, 2, 'I');
        $this->assertEqual(1, count($result3['entries']));
    }

    function testTagDetailsSlice()
    {
        $result0 = $this->calibre->tagDetailsSlice('en', 3, 0, 1, new CalibreFilter());
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertEqual(1, count($result0['entries']));
        $this->assertEqual(0, $result0['page']);
        $this->assertEqual(2, $result0['pages']);
        $result1 = $this->calibre->tagDetailsSlice('en', 3, 1, 1, new CalibreFilter());
        $this->assertEqual(1, count($result1['entries']));
        $this->assertEqual(1, $result1['page']);
        $this->assertEqual(2, $result1['pages']);
    }

    function testTagsInitials()
    {
        $result = $this->calibre->tagsInitials();
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertEqual(5, count($result));
        $this->assertEqual('A', $result[0]->initial);
        $this->assertEqual(1, $result[0]->ctr);
        $this->assertEqual('V', $result[4]->initial);
        $this->assertEqual(1, $result[4]->ctr);
    }

    function testTagsNamesForInitial()
    {
        $result = $this->calibre->tagsNamesForInitial('B');
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertEqual(2, count($result));
        $this->assertEqual(1, $result[0]->anzahl);
        $this->assertEqual('Belletristik & Literatur', $result[0]->name);
    }

    function testTimestampOrderedTitlesSlice()
    {
        $result0 = $this->calibre->timestampOrderedTitlesSlice('en', 0, 2, new CalibreFilter());
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertEqual(2, count($result0['entries']));
        $this->assertEqual(0, $result0['page']);
        $this->assertEqual(4, $result0['pages']);
        $this->assertEqual(7, $result0['entries'][0]->id);
        $this->assertEqual(6, $result0['entries'][1]->id);
        $result1 = $this->calibre->timestampOrderedTitlesSlice('en', 1, 2, new CalibreFilter());
        $this->assertEqual(2, count($result1['entries']));
        $this->assertEqual(1, $result1['page']);
        $this->assertEqual(4, $result1['pages']);
        $this->assertEqual(5, $result1['entries'][0]->id);
        $this->assertEqual(4, $result1['entries'][1]->id);
        $result3 = $this->calibre->timestampOrderedTitlesSlice('en', 3, 2, new CalibreFilter());
        $this->assertEqual(1, count($result3['entries']));
        $this->assertEqual(3, $result3['page']);
        $this->assertEqual(4, $result3['pages']);
        $this->assertEqual(1, $result3['entries'][0]->id);
        $no_result = $this->calibre->timestampOrderedTitlesSlice('en', 100, 2, new CalibreFilter());
        $this->assertEqual(0, count($no_result['entries']));
        $this->assertEqual(100, $no_result['page']);
        $this->assertEqual(4, $no_result['pages']);

        $result0 = $this->calibre->timestampOrderedTitlesSlice('en', 0, 2, new CalibreFilter($lang = 3));
        $this->assertEqual(1, count($result0['entries']));
        $this->assertEqual(1, $result0['pages']);
        $result0 = $this->calibre->timestampOrderedTitlesSlice('en', 1, 2, new CalibreFilter($lang = null, $tag = 21));
        $this->assertEqual(2, count($result0['entries']));
        $this->assertEqual(1, $result0['page']);
        $this->assertEqual(3, $result0['pages']);
        $result0 = $this->calibre->timestampOrderedTitlesSlice('en', 0, 2, new CalibreFilter($lang = 3, $tag = 21));
        $this->assertEqual(0, count($result0['entries']));
        $this->assertEqual(0, $result0['pages']);
        $result0 = $this->calibre->timestampOrderedTitlesSlice('en', 0, 2, new CalibreFilter($lang = 2, $tag = 3));
        $this->assertEqual(1, count($result0['entries']));
        $this->assertEqual(1, $result0['pages']);
    }

    function testPubdateOrderedTitlesSlice()
    {
        $result0 = $this->calibre->pubdateOrderedTitlesSlice('en', 0, 2, new CalibreFilter());
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertEqual(2, count($result0['entries']));
        $this->assertEqual(0, $result0['page']);
        $this->assertEqual(4, $result0['pages']);
        $this->assertEqual(7, $result0['entries'][0]->id);
        $this->assertEqual(6, $result0['entries'][1]->id);
        $result1 = $this->calibre->pubdateOrderedTitlesSlice('en', 1, 2, new CalibreFilter());
        $this->assertEqual(2, count($result1['entries']));
        $this->assertEqual(1, $result1['page']);
        $this->assertEqual(4, $result1['pages']);
        $this->assertEqual(5, $result1['entries'][0]->id);
    }

    function testLastmodifiedOrderedTitlesSlice()
    {
        $result0 = $this->calibre->lastmodifiedOrderedTitlesSlice('en', 0, 2, new CalibreFilter());
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertEqual(2, count($result0['entries']));
        $this->assertEqual(0, $result0['page']);
        $this->assertEqual(4, $result0['pages']);
        $this->assertEqual(7, $result0['entries'][0]->id);
        $this->assertEqual(6, $result0['entries'][1]->id);
        $result1 = $this->calibre->lastmodifiedOrderedTitlesSlice('en', 1, 2, new CalibreFilter());
        $this->assertEqual(2, count($result1['entries']));
        $this->assertEqual(1, $result1['page']);
        $this->assertEqual(4, $result1['pages']);
        $this->assertEqual(5, $result1['entries'][0]->id);
        $this->assertEqual(1, $result1['entries'][1]->id);
    }

    function testTitlesSlice()
    {
        $result0 = $this->calibre->titlesSlice('en', 0, 2, new CalibreFilter());
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertEqual(2, count($result0['entries']));
        $this->assertEqual(0, $result0['page']);
        $this->assertEqual(4, $result0['pages']);
        $result1 = $this->calibre->titlesSlice('en', 1, 2, new CalibreFilter());
        $this->assertEqual(2, count($result1['entries']));
        $this->assertEqual(1, $result1['page']);
        $this->assertEqual(4, $result1['pages']);
        $result3 = $this->calibre->titlesSlice('en', 3, 2, new CalibreFilter());
        $this->assertEqual(1, count($result3['entries']));
        $this->assertEqual(3, $result3['page']);
        $this->assertEqual(4, $result3['pages']);
        $no_result = $this->calibre->titlesSlice('en', 100, 2, new CalibreFilter());
        $this->assertEqual(0, count($no_result['entries']));
        $this->assertEqual(100, $no_result['page']);
        $this->assertEqual(4, $no_result['pages']);

        $result0 = $this->calibre->titlesSlice('en', 0, 2, new CalibreFilter($lang = 3));
        $this->assertEqual(1, count($result0['entries']));
        $this->assertEqual(1, $result0['pages']);
        $result0 = $this->calibre->titlesSlice('en', 1, 2, new CalibreFilter($lang = null, $tag = 21));
        $this->assertEqual(2, count($result0['entries']));
        $this->assertEqual(1, $result0['page']);
        $this->assertEqual(3, $result0['pages']);
        $result0 = $this->calibre->titlesSlice('en', 0, 2, new CalibreFilter($lang = 3, $tag = 21));
        $this->assertEqual(0, count($result0['entries']));
        $this->assertEqual(0, $result0['pages']);
        $result0 = $this->calibre->titlesSlice('en', 0, 2, new CalibreFilter($lang = 2, $tag = 3));
        $this->assertEqual(1, count($result0['entries']));
        $this->assertEqual(1, $result0['pages']);
    }

    function testCount()
    {
        $count = 'select count(*) from books where lower(title) like :search';
        $params = array('search' => '%i%');
        $result = $this->calibre->count($count, $params);
        $this->assertEqual(6, $result);
    }

    function testTitlesSliceSearch()
    {
        $result0 = $this->calibre->titlesSlice('en', 0, 2, new CalibreFilter(), 'I');
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertEqual(2, count($result0['entries']));
        $this->assertEqual(0, $result0['page']);
        $this->assertEqual(3, $result0['pages']);
        $result1 = $this->calibre->titlesSlice('en', 1, 2, new CalibreFilter(), 'I');
        $this->assertEqual(2, count($result1['entries']));
        $result3 = $this->calibre->titlesSlice('en', 2, 2, new CalibreFilter(), 'I');
        $this->assertEqual(2, count($result3['entries']));
    }

    function testAuthorDetails()
    {
        $result = $this->calibre->authorDetails(7);
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertNotNull($result);
        $this->assertEqual('Lessing, Gotthold Ephraim', $result['author']->sort);
    }

    function testTagDetails()
    {
        $result = $this->calibre->tagDetails(3);
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertNotNull($result);
        $this->assertEqual('Fachbücher', $result['tag']->name);
        $this->assertEqual(2, count($result['books']));
    }

    function testTitle()
    {
        $result = $this->calibre->title(3);
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertFalse($result === FALSE);
        $this->assertEqual('Der seltzame Springinsfeld', $result->title);
    }

    function testTitleCover()
    {
        $result = $this->calibre->titleCover(3);
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertNotNull($result);
        $this->assertEqual('cover.jpg', basename($result));
    }

    function testTitleFile()
    {
        $result = $this->calibre->titleFile(3, 'Der seltzame Springinsfeld - Hans Jakob Christoffel von Grimmelshausen.epub');
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertNotNull($result);
        $this->assertEqual('Der seltzame Springinsfeld - Hans Jakob Christoffel von Grimmelshausen.epub', basename($result));
    }

    function testTitleDetails()
    {
        $result = $this->calibre->titleDetails('en', 3);
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertFalse($result === FALSE);
        $this->assertEqual('Der seltzame Springinsfeld', $result['book']->title);
        $this->assertEqual('Fachbücher', $result['tags'][0]->name);
        $this->assertEqual('Serie Grimmelshausen', $result['series'][0]->name);
    }

    function testTitleDetailsOpds()
    {
        $book = $this->calibre->title(3);
        $result = $this->calibre->titleDetailsOpds($book);
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertFalse($result === FALSE);
        $this->assertEqual('Der seltzame Springinsfeld', $result['book']->title);
        $this->assertEqual('Fachbücher', $result['tags'][0]->name);
    }

    function testTitleDetailsFilteredOpds()
    {
        $books = $this->calibre->titlesSlice('en', 1, 2, new CalibreFilter());
        $this->assertEqual(2, count($books['entries']));
        $result = $this->calibre->titleDetailsFilteredOpds($books['entries']);
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertFalse($result === FALSE);
        $this->assertEqual(1, count($result));
    }

    function testSeriesSlice()
    {
        $result0 = $this->calibre->seriesSlice(0, 2);
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertEqual(2, count($result0['entries']));
        $this->assertEqual(0, $result0['page']);
        $this->assertEqual(2, $result0['pages']);
        $result1 = $this->calibre->seriesSlice(1, 2);
        $this->assertEqual(1, count($result1['entries']));
        $this->assertEqual(1, $result1['page']);
        $this->assertEqual(2, $result1['pages']);
    }

    function testSeriesSliceSearch()
    {
        $result0 = $this->calibre->seriesSlice(0, 2, 'I');
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertEqual(2, count($result0['entries']));
        $this->assertEqual(0, $result0['page']);
        $this->assertEqual(2, $result0['pages']);
        $result1 = $this->calibre->seriesSlice(1, 2, 'I');
        $this->assertEqual(1, count($result1['entries']));
    }

    function testSeriesDetailsSlice()
    {
        $result0 = $this->calibre->seriesDetailsSlice('en', 1, 0, 1, new CalibreFilter());
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertEqual(1, count($result0['entries']));
        $this->assertEqual(0, $result0['page']);
        $this->assertEqual(2, $result0['pages']);
        $result1 = $this->calibre->seriesDetailsSlice('en', 1, 1, 1, new CalibreFilter());
        $this->assertEqual(1, count($result1['entries']));
        $this->assertEqual(1, $result1['page']);
        $this->assertEqual(2, $result1['pages']);
    }

    function testSeriesDetails()
    {
        $result = $this->calibre->seriesDetails(5);
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertNotNull($result);
        $this->assertEqual('Serie Rilke', $result['series']->name);
        $this->assertEqual(1, count($result['books']));
    }

    function testSeriesInitials()
    {
        $result = $this->calibre->seriesInitials();
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertEqual(1, count($result));
        $this->assertEqual('S', $result[0]->initial);
        $this->assertEqual(3, $result[0]->ctr);
    }

    function testSeriesNamesForInitial()
    {
        $result = $this->calibre->seriesNamesForInitial('S');
        $this->assertEqual(0, $this->calibre->last_error);
        $this->assertEqual(3, count($result));
        $this->assertEqual(2, $result[0]->anzahl);
        $this->assertEqual('Serie Grimmelshausen', $result[0]->name);
    }

}

?>
