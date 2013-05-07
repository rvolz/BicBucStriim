<?php
set_include_path("tests");
require_once('lib/simpletest/autorun.php');
require_once('lib/BicBucStriim/bicbucstriim.php');

class TestOfCustomColumns extends UnitTestCase {
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

	# Lots of ccs -- one with multiple values
	function testCustomColumns() {				
		$ccs = $this->bbs->customColumns(7);
		#print_r($ccs);
		$this->assertEqual(9, sizeof($ccs));
		$this->assertEqual('col2a, col2b', $ccs['Col2']['value']);
	}

	# Ignore series ccs for now
	function testCustomColumnsIgnoreSeries() {				
		$ccs = $this->bbs->customColumns(5);
		#print_r($ccs);
		$this->assertEqual(0, sizeof($ccs));
	}

	# Only one cc
	function testCustomColumnsJustOneCC() {				
		$ccs = $this->bbs->customColumns(1);
		$this->assertEqual(1, sizeof($ccs));
	}
}

?>
