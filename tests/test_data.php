<?php
set_include_path("tests");
require_once('lib/simpletest/autorun.php');
require_once('lib/BicBucStriim/bicbucstriim.php');
require_once('lib/BicBucStriim/calibre_filter.php');

class TestOfBicBucStriimData extends UnitTestCase {

	const CDB1 = './tests/fixtures/metadata_empty.db';
	const CDB2 = './tests/fixtures/lib2/metadata.db';
	const CDB3 = './tests/fixtures/lib3/metadata.db';

	const DB2 = './tests/fixtures/data.db';

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

	function testIdTemplates() {		
		// initially no templates
		$result = $this->bbs->idTemplates();
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertEqual(0, count($result));
		# add 1 template
		$result = $this->bbs->addIdTemplate('test1', 'http://test1/%id$/', 'test1Label');
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertNotNull($result);
		$this->assertEqual('test1', $result->name);
		$this->assertEqual('http://test1/%id$/', $result->val);
		$this->assertEqual('test1Label', $result->label);
		$result = $this->bbs->idTemplates();
		$this->assertEqual(1, count($result));
		# add more templates
		$result = $this->bbs->addIdTemplate('test2', 'http://test2/%id$/', 'test2Label');
		$result = $this->bbs->addIdTemplate('test3', 'http://test3/%id$/', 'test3Label');
		$result = $this->bbs->addIdTemplate('test4', 'http://test4/%id$/', 'test4Label');
		$result = $this->bbs->idTemplates();
		$this->assertEqual(4, count($result));
		# check for a single template
		$result = $this->bbs->idTemplate('test1');
		$this->assertEqual(0, $this->bbs->last_error);
		$this->assertEqual('test1', $result->name);
		$this->assertEqual('http://test1/%id$/', $result->val);
		$this->assertEqual('test1Label', $result->label);
		# adding a template with an exisiting name should fail
		$this->assertNull($this->bbs->addIdTemplate('test1', 'http://test1/%id$/', 'test1Label'));
		# modify an exisiting template
		$result = $this->bbs->changeIdTemplate('test1', 'http://test1/%id$/', 'test1aLabel');
		$this->assertEqual('test1', $result->name);
		$this->assertEqual('http://test1/%id$/', $result->val);
		$this->assertEqual('test1aLabel', $result->label);
		# modifying the name  should fail
		$this->assertNull($this->bbs->changeIdTemplate('test1a', 'http://test1/%id$/', 'test1Label'));		
	}


}
?>
