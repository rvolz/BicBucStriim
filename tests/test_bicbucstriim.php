<?php
set_include_path("tests");
require_once('lib/simpletest/autorun.php');
require_once('lib/BicBucStriim/bicbucstriim.php');

class TestOfBicBucStriim extends UnitTestCase {

	const CDB1 = './tests/fixtures/metadata_empty.db';
	const CDB2 = './tests/fixtures/lib2/metadata.db';
	const CDB3 = './tests/fixtures/lib3/metadata.db';

	const DB2 = './tests/fixtures/data2.db';

	const DATA = './tests/data';
	const DATADB = './tests/data/data.db';

	var $bbs;

	function setUp() {
		if (file_exists(self::DATA))
			system("rm -rf ".self::DATA);	
    mkdir(self::DATA);
    chmod(self::DATA,0777);
    copy(self::DB2, self::DATADB);
    $this->bbs = new BicBucStriim(self::DATADB);
    $this->bbs->openCalibreDb(self::CDB2);
	}

	function tearDown() {
		$this->bbs = NULL;
		system("rm -rf ".self::DATA);
	}

	function testOpenCalibreEmptyDb() {				
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertTrue($this->bbs->dbOk());
		$this->bbs->openCalibreDb(self::CDB1);
		$this->assertTrue($this->bbs->libraryOk());		
	}

	function testOpenCalibreNotExistingDb() {				
		$this->assertTrue($this->bbs->dbOk());
		$this->bbs->openCalibreDb(self::CDB3);
		$this->assertFalse($this->bbs->libraryOk());
		$this->assertEqual(0, $this->bbs->last_error);
	}

	function testLast30() {		
		$result = $this->bbs->last30Books();
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertFalse($result === FALSE);
		$this->assertEqual(7, count($result));
	}

	function testAuthorsSlice() {
		$result0 = $this->bbs->authorsSlice(0,2);
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertEqual(2, count($result0['entries']));
		$this->assertEqual(0, $result0['page']);
		$this->assertEqual(3, $result0['pages']);
		$result1 = $this->bbs->authorsSlice(1,2);
		$this->assertEqual(2, count($result1['entries']));
		$this->assertEqual(1, $result1['page']);
		$this->assertEqual(3, $result1['pages']);		
		$result2 = $this->bbs->authorsSlice(2,2);
		$this->assertEqual(2, count($result2['entries']));
		$this->assertEqual(2, $result2['page']);
		$this->assertEqual(3, $result2['pages']);		
		$no_result = $this->bbs->authorsSlice(100,2);		
		$this->assertEqual(0, count($no_result['entries']));
		$this->assertEqual(100, $no_result['page']);
		$this->assertEqual(3, $no_result['pages']);				
	}

	function testAuthorsSliceSearch() {
		$result0 = $this->bbs->authorsSlice(0,2,'I');
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertEqual(2, count($result0['entries']));
		$this->assertEqual(0, $result0['page']);
		$this->assertEqual(3, $result0['pages']);		
		$result1 = $this->bbs->authorsSlice(1,2,'I');
		$this->assertEqual(2, count($result1['entries']));
		$result3 = $this->bbs->authorsSlice(2,2,'I');
		$this->assertEqual(1, count($result3['entries']));
	}

	function testAuthorDetailsSlice() {
		$result0 = $this->bbs->authorDetailsSlice(6,0,1);
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertEqual(1, count($result0['entries']));
		$this->assertEqual(0, $result0['page']);
		$this->assertEqual(2, $result0['pages']);
		$result1 = $this->bbs->authorDetailsSlice(6,1,1);
		$this->assertEqual(1, count($result1['entries']));
		$this->assertEqual(1, $result1['page']);
		$this->assertEqual(2, $result1['pages']);		
	}

	function testAuthorsInitials() {
		$result = $this->bbs->authorsInitials();
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertEqual(5, count($result));
		$this->assertEqual('E', $result[0]->initial);
		$this->assertEqual(1, $result[0]->ctr);
		$this->assertEqual('R', $result[4]->initial);
		$this->assertEqual(2, $result[4]->ctr);		
	}

	function testAuthorsNamesForInitial() {
		$result = $this->bbs->authorsNamesForInitial('R');
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertEqual(2, count($result));
		$this->assertEqual(1, $result[0]->anzahl);
		$this->assertEqual('Rilke, Rainer Maria', $result[0]->sort);
	}

	function testTagsSlice() {
		$result0 = $this->bbs->tagsSlice(0,2);
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertEqual(2, count($result0['entries']));
		$this->assertEqual(0, $result0['page']);
		$this->assertEqual(3, $result0['pages']);
		$result1 = $this->bbs->tagsSlice(1,2);
		$this->assertEqual(2, count($result1['entries']));
		$this->assertEqual(1, $result1['page']);
		$this->assertEqual(3, $result1['pages']);		
		$result2 = $this->bbs->tagsSlice(2,2);
		$this->assertEqual(2, count($result2['entries']));
		$this->assertEqual(2, $result2['page']);
		$this->assertEqual(3, $result2['pages']);		
		$no_result = $this->bbs->tagsSlice(100,2);		
		$this->assertEqual(0, count($no_result['entries']));
		$this->assertEqual(100, $no_result['page']);
		$this->assertEqual(3, $no_result['pages']);				
	}

	function testTagsSliceSearch() {
		$result0 = $this->bbs->tagsSlice(0,2,'I');
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertEqual(2, count($result0['entries']));
		$this->assertEqual(0, $result0['page']);
		$this->assertEqual(3, $result0['pages']);		
		$result1 = $this->bbs->tagsSlice(1,2,'I');
		$this->assertEqual(2, count($result1['entries']));
		$result3 = $this->bbs->tagsSlice(2,2,'I');
		$this->assertEqual(1, count($result3['entries']));
	}

	function testTagDetailsSlice() {
		$result0 = $this->bbs->tagDetailsSlice(3,0,1);
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertEqual(1, count($result0['entries']));
		$this->assertEqual(0, $result0['page']);
		$this->assertEqual(2, $result0['pages']);
		$result1 = $this->bbs->tagDetailsSlice(3,1,1);
		$this->assertEqual(1, count($result1['entries']));
		$this->assertEqual(1, $result1['page']);
		$this->assertEqual(2, $result1['pages']);		
	}

	function testTagsInitials() {
		$result = $this->bbs->tagsInitials();
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertEqual(5, count($result));
		$this->assertEqual('A', $result[0]->initial);
		$this->assertEqual(1, $result[0]->ctr);
		$this->assertEqual('V', $result[4]->initial);
		$this->assertEqual(1, $result[4]->ctr);		
	}

	function testTagsNamesForInitial() {
		$result = $this->bbs->tagsNamesForInitial('B');
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertEqual(2, count($result));
		$this->assertEqual(1, $result[0]->anzahl);
		$this->assertEqual('Belletristik & Literatur', $result[0]->name);
	}

	function testTitlesSlice() {
		$result0 = $this->bbs->titlesSlice(0,2);
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertEqual(2, count($result0['entries']));
		$this->assertEqual(0, $result0['page']);
		$this->assertEqual(4, $result0['pages']);
		$result1 = $this->bbs->titlesSlice(1,2);
		$this->assertEqual(2, count($result1['entries']));
		$this->assertEqual(1, $result1['page']);
		$this->assertEqual(4, $result1['pages']);		
		$result3 = $this->bbs->titlesSlice(3,2);
		$this->assertEqual(1, count($result3['entries']));
		$this->assertEqual(3, $result3['page']);
		$this->assertEqual(4, $result3['pages']);		
		$no_result = $this->bbs->titlesSlice(100,2);		
		$this->assertEqual(0, count($no_result['entries']));
		$this->assertEqual(100, $no_result['page']);
		$this->assertEqual(4, $no_result['pages']);				
	}

	function testCount($value='') {
		$count = 'select count(*) from books where lower(title) like \'%i%\'';
		$result = $this->bbs->count($count);
		$this->assertEqual(6,$result);
	}

	function testTitlesSliceSearch() {
		$result0 = $this->bbs->titlesSlice(0,2,'I');
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertEqual(2, count($result0['entries']));
		$this->assertEqual(0, $result0['page']);
		$this->assertEqual(3, $result0['pages']);		
		$result1 = $this->bbs->titlesSlice(1,2,'I');
		$this->assertEqual(2, count($result1['entries']));
		$result3 = $this->bbs->titlesSlice(2,2,'I');
		$this->assertEqual(2, count($result3['entries']));
	}

	function testAuthorDetails() {
		$result = $this->bbs->authorDetails(7);
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertNotNull($result);
		$this->assertEqual('Lessing, Gotthold Ephraim',$result['author']->sort);
	}

	function testTagDetails() {
		$result = $this->bbs->tagDetails(3);
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertNotNull($result);
		$this->assertEqual('Fachbücher',$result['tag']->name);
		$this->assertEqual(2,count($result['books']));		
	}

	function testTitle() {
		$result = $this->bbs->title(3);
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertFalse($result === FALSE);
		$this->assertEqual('Der seltzame Springinsfeld',$result->title);
	}

	function testTitleCover() {
		$result = $this->bbs->titleCover(3);
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertNotNull($result);
		$this->assertEqual('cover.jpg',basename($result));
	}

	function testTitleThumbnail() {
		$result = $this->bbs->titleThumbnail(3, true);
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertNotNull($result);
		$this->assertEqual('thumb_3.png',basename($result));
	}

	function testClearThumbnail() {
		$result = $this->bbs->titleThumbnail(3, true);
		$this->assertEqual('thumb_3.png',basename($result));
		$this->assertTrue($this->bbs->clearThumbnails());
		$this->assertFalse(file_exists($result));
	}

	function testTitleFile() {
		$result = $this->bbs->titleFile(3, 'Der seltzame Springinsfeld - Hans Jakob Christoffel von Grimmelshausen.epub');
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertNotNull($result);
		$this->assertEqual('Der seltzame Springinsfeld - Hans Jakob Christoffel von Grimmelshausen.epub',basename($result));
	}

	function testTitleDetails() {
		$result = $this->bbs->titleDetails(3);
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertFalse($result === FALSE);
		$this->assertEqual('Der seltzame Springinsfeld',$result['book']->title);
		$this->assertEqual('Fachbücher',$result['tags'][0]->name);
		$this->assertEqual('Serie Grimmelshausen',$result['series'][0]->name);
	}

	function testTitleDetailsOpds() {
		$book = $this->bbs->title(3);
		$result = $this->bbs->titleDetailsOpds($book);
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertFalse($result === FALSE);
		$this->assertEqual('Der seltzame Springinsfeld',$result['book']->title);
		$this->assertEqual('Fachbücher',$result['tags'][0]->name);		
	}

	function testTitleDetailsFilteredOpds() {
		$books = $this->bbs->titlesSlice(1,2);
		$this->assertEqual(2, count($books['entries']));
		$result = $this->bbs->titleDetailsFilteredOpds($books['entries']);
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertFalse($result === FALSE);
		$this->assertEqual(1, count($result));
	}

	function testSeriesSlice() {
		$result0 = $this->bbs->seriesSlice(0,2);
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertEqual(2, count($result0['entries']));
		$this->assertEqual(0, $result0['page']);
		$this->assertEqual(2, $result0['pages']);
		$result1 = $this->bbs->seriesSlice(1,2);
		$this->assertEqual(1, count($result1['entries']));
		$this->assertEqual(1, $result1['page']);
		$this->assertEqual(2, $result1['pages']);		
	}

	function testSeriesSliceSearch() {
		$result0 = $this->bbs->seriesSlice(0,2,'I');
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertEqual(2, count($result0['entries']));
		$this->assertEqual(0, $result0['page']);
		$this->assertEqual(2, $result0['pages']);		
		$result1 = $this->bbs->seriesSlice(1,2,'I');
		$this->assertEqual(1, count($result1['entries']));
	}

	function testSeriesDetailsSlice() {
		$result0 = $this->bbs->seriesDetailsSlice(1,0,1);
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertEqual(1, count($result0['entries']));
		$this->assertEqual(0, $result0['page']);
		$this->assertEqual(2, $result0['pages']);
		$result1 = $this->bbs->seriesDetailsSlice(1,1,1);
		$this->assertEqual(1, count($result1['entries']));
		$this->assertEqual(1, $result1['page']);
		$this->assertEqual(2, $result1['pages']);		
	}

	function testSeriesDetails() {
		$result = $this->bbs->seriesDetails(3);
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertNotNull($result);
		$this->assertEqual('Serie Rilke',$result['series']->name);
		$this->assertEqual(1,count($result['books']));		
	}

	function testSeriesInitials() {
		$result = $this->bbs->seriesInitials();
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertEqual(1, count($result));
		$this->assertEqual('S', $result[0]->initial);
		$this->assertEqual(3, $result[0]->ctr);
	}

	function testSeriesNamesForInitial() {
		$result = $this->bbs->seriesNamesForInitial('S');
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertEqual(3, count($result));
		$this->assertEqual(2, $result[0]->anzahl);
		$this->assertEqual('Serie Grimmelshausen', $result[0]->name);
	}

}
?>
