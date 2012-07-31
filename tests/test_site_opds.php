<?php
set_include_path("tests");
require_once('lib/simpletest/autorun.php');
require_once('lib/simpletest/web_tester.php');
require_once('lib/BicBucStriim/opds_generator.php'); 
/*
Testing the OPDS feeds
 */
class TestOfSiteOpds extends WebTestCase {

	# The external integration test site
	private $testhost = 'http://localhost:8080/bbs/';

	# Validation helper: validate opds
	function opdsValidate($feed, $version) {
		$cmd = 'cd ~/lib/java/opds-validator;java -jar OPDSvalidator.jar -v'.$version.' '.$feed;
		$res = system($cmd);
		if ($res != '') {
			echo 'OPDS validation error: '.$res;
			return false;
		} else
			return true;
	}

	# Validation helper
	function catalogValidate($feed, $mime, $version) {
		echo "Validating catalog ".$feed." for OPDS version ".$version."\n";
		$this->assertTrue($this->get($feed));
		$this->assertResponse(200);
		$this->assertMime($mime);
		$this->assertTrue($this->opdsValidate($feed,$version));
	}

	function testRootCatalog() {
		$this->catalogValidate($this->testhost.'opds/', OpdsGenerator::OPDS_MIME_NAV, '1.0');
		$this->catalogValidate($this->testhost.'opds/', OpdsGenerator::OPDS_MIME_NAV, '1.1');
	}

	function testNewestCatalog() {
		$this->catalogValidate($this->testhost.'opds/newest/', OpdsGenerator::OPDS_MIME_ACQ, '1.0');
		$this->catalogValidate($this->testhost.'opds/newest/', OpdsGenerator::OPDS_MIME_ACQ, '1.1');
	}
}
?>
