<?php
set_include_path("tests");
require_once('lib/simpletest/autorun.php');

/*
Extended test for the functionality of the tag details page.
 */
class TestOfTagDetails extends WebTestCase {

	# The external integration test site
	private $testhost = 'http://localhost:8080/bbs/';

	/*
	Check the tag details, the display of information
	 */
	public function testTagDetails() {
		$this->assertTrue($this->get($this->testhost.'tags/3/0/'));
		$this->assertTitle('BicBucStriim :: Tag Details');	
		$this->assertText('Books tagged with "Fachbücher"');		
		$this->assertLink('Glücksritter, Die (2012)');		
		$this->assertLink('seltzame Springinsfeld, Der (2012)');		
	}
}
?>
