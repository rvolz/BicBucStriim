<?php
set_include_path("tests");
require_once('tests/lib/simpletest/autorun.php');
#require_once('lib/rb.php');

require_once('bicbucstriim.php');

class TestOfIndex extends UnitTestCase {

	const DB1 = './tests/fixtures/metadata_empty.db';
	const DB2 = './tests/fixtures/lib2/metadata.db';
	const DB3 = './tests/fixtures/lib3/metadata.db';

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
    $this->bbs = new BicBucStriim(self::DB2);
	}

	function tearDown() {
		$this->bbs = NULL;
	}

	function testOpenCalibre() {				
		$bbs2 = new BicBucStriim(self::DB1);
		$this->assertTrue($bbs2->libraryOk());
		$this->assertEqual(0, $bbs2->last_error);
		$bbs2 = NULL;
		$bbs3 = new BicBucStriim(self::DB3);
		$this->assertEqual(0, $bbs3->last_error);
		$this->assertFalse($bbs3->libraryOk());		
		$bbs3 = NULL;
	}

	function testLast30() {		
		$result = $this->bbs->last30Books();
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertFalse($result === FALSE);
		$this->assertEqual(4, count($result));
	}

	function testAllAuthors() {		
		$result = $this->bbs->allAuthors();
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertFalse($result === FALSE);
		$this->assertEqual(7, count($result));
	}

	function testAllTags() {		
		$result = $this->bbs->allTags();
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertFalse($result === FALSE);
		$this->assertEqual(5, count($result));
	}

	function testAllTitles() {		
		$result = $this->bbs->allTitles();
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertFalse($result === FALSE);
		$this->assertEqual(7, count($result));
	}

	function testAuthorDetails() {
		$result = $this->bbs->authorDetails(3);
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertNotNull($result);
		$this->assertEqual('Eintest3, Tester',$result['author']->sort);
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
		$this->assertEqual('Ein Test (#3)',$result->title);
	}

	function testTitleCover() {
		$result = $this->bbs->titleCover(3);
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertNotNull($result);
		$this->assertEqual('cover.jpg',basename($result));
	}

	function testTitleFile() {
		$result = $this->bbs->titleFile(3, 'Ein Test (#3) - Tester Eintest3.epub');
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertNotNull($result);
		$this->assertEqual('Ein Test (#3) - Tester Eintest3.epub',basename($result));
	}

	function testTitleDetails() {
		$result = $this->bbs->titleDetails(3);
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertFalse($result === FALSE);
		$this->assertEqual('Ein Test (#3)',$result['book']->title);
		$this->assertEqual('Fachbücher',$result['tags'][0]->name);
	}
}
?>
