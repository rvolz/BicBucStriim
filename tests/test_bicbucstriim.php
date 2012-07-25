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
		/*
		$_SERVER['SERVER_NAME'] = 'slim';
    $_SERVER['SERVER_PORT'] = '80';
    $_SERVER['SCRIPT_NAME'] = '/bbs/index.php';
    $_SERVER['REQUEST_URI'] = '/bbs/titles/';
    $_SERVER['PATH_INFO'] = '/titles/';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['QUERY_STRING'] = '';
    $_SERVER['HTTPS'] = '';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    unset($_SERVER['CONTENT_TYPE'], $_SERVER['CONTENT_LENGTH']);        
		*/
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

	function testAllAuthors() {		
		$result = $this->bbs->allAuthors();
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertFalse($result === FALSE);
		$this->assertEqual(11, count($result));
		$this->assertEqual('E', $result[0]['initial']);
		$this->assertEqual('R', $result[8]['initial']);			
	}

	function testAllTags() {		
		$result = $this->bbs->allTags();
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertFalse($result === FALSE);
		$this->assertEqual(11, count($result));
		$this->assertEqual('A', $result[0]['initial']);
		$this->assertEqual('V', $result[9]['initial']);					
	}

	function testAllTitles() {		
		$result = $this->bbs->allTitles();
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertFalse($result === FALSE);
		$this->assertEqual(13, count($result));
		$this->assertEqual('G', $result[0]['initial']);
		$this->assertEqual('Z', $result[11]['initial']);							
	}

	function testTitlesList() {
		$result0 = $this->bbs->titlesList(0,2);
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertEqual(2, count($result0['entries']));
		$this->assertEqual(0, $result0['page']);
		$this->assertEqual(4, $result0['pages']);
		$result1 = $this->bbs->titlesList(1,2);
		$this->assertEqual(2, count($result1['entries']));
		$this->assertEqual(1, $result1['page']);
		$this->assertEqual(4, $result1['pages']);		
		$result3 = $this->bbs->titlesList(3,2);
		$this->assertEqual(1, count($result3['entries']));
		$this->assertEqual(3, $result3['page']);
		$this->assertEqual(4, $result3['pages']);		
		$no_result = $this->bbs->titlesList(100,2);		
		$this->assertEqual(0, count($no_result['entries']));
		$this->assertEqual(100, $no_result['page']);
		$this->assertEqual(4, $no_result['pages']);				
	}

	function testCount($value='') {
		$count = 'select count(*) from books where lower(title) like \'%i%\'';
		$result = $this->bbs->count($count);
		$this->assertEqual(6,$result);
	}

	function testTitlesListSearch() {
		$result0 = $this->bbs->titlesList(0,2,'I');
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertEqual(2, count($result0['entries']));
		$this->assertEqual(0, $result0['page']);
		$this->assertEqual(3, $result0['pages']);		
		$result1 = $this->bbs->titlesList(1,2);
		$this->assertEqual(2, count($result1['entries']));
		$result3 = $this->bbs->titlesList(2,2);
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
		$result = $this->bbs->titleThumbnail(3);
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertNotNull($result);
		$this->assertEqual('thumb_3.png',basename($result));
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
	}
}
?>
