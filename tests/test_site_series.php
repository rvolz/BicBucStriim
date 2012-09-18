<?php
set_include_path("tests");
require_once('lib/simpletest/autorun.php');
require_once('lib/simpletest/web_tester.php');
/*
Simple tests for series pages.
 */
class TestOfSiteSeries extends WebTestCase {

	# The external integration test site
	private $testhost = 'http://localhost:8080/bbs/';

	/*
	Check series overview.
	*/
	public function testSeries() {
		$this->assertTrue($this->get($this->testhost.'serieslist/0/'));
		$this->assertTitle('BicBucStriim :: Series');
	}

	/*
	Check the navigation from series overview to series detail and back
	 */
	public function testNavigationFromSeriesOverviewToDetail() {
		$this->assertTrue($this->get($this->testhost.'serieslist/0/'));
		$this->clickLink('Serie Grimmelshausen 2');
		$this->assertResponse(200);
		$this->assertEqual($this->testhost.'series/1/0/', $this->getUrl());
	}

	/*
	Check the series details, the display of information
	 */
	public function testSeriesDetails() {
		$this->assertTrue($this->get($this->testhost.'series/1/0/'));
		$this->assertTitle('BicBucStriim :: Series Details');	
		$this->assertText('Books in series "Serie Grimmelshausen"');		
		$this->assertLink('seltzame Springinsfeld, Der (2012)');		
		$this->assertLink('Trutz Simplex (2012)');		
	}

}
?>
