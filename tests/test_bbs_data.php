<?php
set_include_path("tests");
require_once('lib/simpletest/autorun.php');
require_once('vendor/rb.php');
require_once('lib/BicBucStriim/data_constants.php');
require_once('lib/BicBucStriim/calibre_thing.php');
require_once('lib/BicBucStriim/data.php');

class TestOfBicBucStriim extends UnitTestCase {

	const DB2 = './tests/fixtures/data2.db';

	const DATA = './tests/data';
	const DATADB = './tests/data/data.db';

	var $data;

	function setUp() {
		if (file_exists(self::DATA))
			system("rm -rf ".self::DATA);	
	    mkdir(self::DATA);
	    chmod(self::DATA,0777);
	    copy(self::DB2, self::DATADB);
	    $this->data = new BbsData(self::DATADB, self::DATA);
	}

	function tearDown() {
		R::nuke();
		$this->data = NULL;
		system("rm -rf ".self::DATA);
	}

	function testCalibreThing() {				
		$this->assertNull($this->data->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, 1));
		$result = $this->data->addCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, 1, 'Author 1');
		$this->assertNotNull($result);
		$this->assertEqual('Author 1', $result->cname);
		$result2 = $this->data->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, 1);
		$this->assertEqual('Author 1', $result2->cname);
	}

	function testEditAuthorThumbnail() {				
		$this->assertTrue($this->data->editAuthorThumbnail(1, 'Author Name', true, 'tests/fixtures/author1.jpg'));
		$this->assertTrue(file_exists(self::DATA.'/authors/author_1_thm.png'));
		$result2 = $this->data->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, 1);
		$this->assertEqual('Author Name', $result2->cname);
		$artefacts = $result2->ownArtefact;
		$this->assertEqual(1, count($artefacts));
		$result = $artefacts[1];
		$this->assertNotNull($result);
		$this->assertEqual(DataConstants::AUTHOR_THUMBNAIL_ARTEFACT, $result->atype);
		$this->assertEqual(self::DATA.'/authors/author_1_thm.png', $result->url);
	}

	function testGetAuthorThumbnail() {				
		$this->assertTrue($this->data->editAuthorThumbnail(1, 'Author Name', true, 'tests/fixtures/author1.jpg'));
		$result = $this->data->getAuthorThumbnail(1);
		$this->assertNotNull($result);
		$this->assertEqual(DataConstants::AUTHOR_THUMBNAIL_ARTEFACT, $result->atype);
		$this->assertEqual(self::DATA.'/authors/author_1_thm.png', $result->url);
	}

	function testDeleteAuthorThumbnail() {				
		$this->assertTrue($this->data->editAuthorThumbnail(1, 'Author Name', true, 'tests/fixtures/author1.jpg'));
		$this->assertNotNull($this->data->getAuthorThumbnail(1));
		$this->assertTrue($this->data->deleteAuthorThumbnail(1));
		$this->assertFalse(file_exists(self::DATA.'/authors/author_1_thm.png'));
		$this->assertNull($this->data->getAuthorThumbnail(1));
		$result2 = $this->data->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, 1);
		$artefacts = $result2->ownArtefact;
		$this->assertEqual(0, count($artefacts));
	}
}
?>

