<?php
set_include_path("tests");
require_once('lib/simpletest/autorun.php');

/*
Extended test for the functionality of the author details page.
 */
class TestOfAuthorDetails extends WebTestCase {

	# The external integration test site
	private $testhost = 'http://localhost:8080/bbs/';

	/*
	Check the author details, the display of information
	 */
	public function testAuthorDetails() {
		$this->assertTrue($this->get($this->testhost.'authors/8'));
		$this->assertTitle('BicBucStriim :: Author Details');	
		$this->assertText('Books by Paul Heyse');
		$this->assertLink('Neues Leben (2012)');		
	}
}
?>
