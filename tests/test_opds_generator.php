<?php
set_include_path("tests");
require_once('lib/simpletest/autorun.php');
require_once('lib/BicBucStriim/opds_generator.php');

class TestOfOpdsGenerator extends UnitTestCase {
	const OPDS_RNG = './tests/fixtures/opds_catalog_1_1.rng';
	const DATA = './tests/data';
	var $gen;

	function setUp() {
		if (file_exists(self::DATA))
			system("rm -rf ".self::DATA);	
    mkdir(self::DATA);
		$this->gen = new OpdsGenerator('/bbs', '0.9.0', date(DATE_ATOM, time()));
	}

	function tearDown() {
		unset($this->gen);
		system("rm -rf ".self::DATA);
	}

	# Validation helper
	function opdsValidate($feed) {
		$res = system('jing '.self::OPDS_RNG.' '.$feed);
		if ($res != '') {
			echo 'OPDS validation error: '.$res;
			return false;
		} else
			return true;
	}

	function testRootCatalogValidation() {
		$feed = self::DATA.'/feed.xml';
		$xml = $this->gen->rootCatalog($feed);
		$this->assertTrue(file_exists($feed));		
		$this->assertTrue($this->opdsValidate($feed));
	}
}
?>
