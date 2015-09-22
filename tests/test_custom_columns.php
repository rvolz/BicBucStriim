<?php
set_include_path("tests:vendor");
require_once('simpletest/simpletest/autorun.php');
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
	    $this->calibre = new Calibre(self::CDB2);
	}

	function tearDown() {
		$this->calibre = NULL;
	}

	# Lots of ccs -- one with multiple values
	function testCustomColumns() {				
		$ccs = $this->calibre->customColumns(7);
		#print_r($ccs);
		$this->assertEqual(9, sizeof($ccs));
		$this->assertEqual('col2a, col2b', $ccs['Col2']['value']);
	}

	# Ignore series ccs for now
	function testCustomColumnsIgnoreSeries() {				
		$ccs = $this->calibre->customColumns(5);
		#print_r($ccs);
		$this->assertEqual(0, sizeof($ccs));
	}

	# Only one cc
	function testCustomColumnsJustOneCC() {				
		$ccs = $this->calibre->customColumns(1);
		$this->assertEqual(1, sizeof($ccs));
	}
}

?>
