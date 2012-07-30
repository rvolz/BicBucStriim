<?php
set_include_path("tests");
require_once('lib/simpletest/autorun.php');
require_once('lib/simpletest/web_tester.php');
/*
Testing the OPDS feeds
 */
class TestOfSiteOpds extends WebTestCase {

	# The external integration test site
	private $testhost = 'http://localhost:8080/bbs/';

	function testRootCatalog() {
		$this->assertTrue($this->get($this->testhost.'opds'));
		$this->assertResponse(200);
		$this->assertMime('application/atom+xml;profile=opds-catalog;kind=navigation');
	}
}
?>
