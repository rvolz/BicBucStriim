<?php
set_include_path("tests");
require_once('lib/simpletest/autorun.php');
require_once('lib/simpletest/web_tester.php');
/*
Simple tests for the overview pages.
 */
class TestOfSiteSimple extends WebTestCase {

	# The external integration test site
	private $testhost = 'http://localhost:8080/bbs/';


	/*
	If a valid calibre dir is saved the start page shows the last 30 books.
	*/
	public function testStartPage() {
		$this->assertTrue($this->get($this->testhost));
		$this->assertResponse(200);
		$this->assertTitle('BicBucStriim :: Most recent');	
		$this->assertLink('Stones of Venice, Volume II, The (2012)');
		$this->assertLink('Neues Leben (2012)');
	}

	/*
	Check titles overview.
	*/
	public function testTitles() {
		$this->assertTrue($this->get($this->testhost.'titleslist/0/'));
		$this->assertTitle('BicBucStriim :: Books');
	}

	/*
	Check the navigation from titles overview to title detail and back
	 */
	public function testNavigationFromTitlesOverviewToDetail() {
		$this->assertTrue($this->get($this->testhost.'titleslist/0/'));
		$this->clickLink('Lob der Faulheit (2012)');
		$this->assertResponse(200);
		$this->assertEqual($this->testhost.'titles/1/', $this->getUrl());
	}

	/*
	Check thumbnail generation for titles overview
	 */
	public function testTitleThumbnails() {
		$this->assertTrue($this->get($this->testhost.'titleslist/0/'));
		$this->assertPattern('/<img src="\/bbs\/titles\/4\/thumbnail"/');
		$this->assertTrue($this->get($this->testhost.'titles/4/thumbnail/'));
		$this->assertResponse(200);
		$this->assertMime('image/jpeg;base64');		
	}

	/*
	Check authors overview.
	*/
	public function testAuthors() {
		$this->assertTrue($this->get($this->testhost.'authorslist/0/'));
		$this->assertTitle('BicBucStriim :: Authors');
	}

	/*
	Check the navigation from authors overview to author detail 
	 */
	public function testNavigationFromAuthorsOverviewToDetail() {
		$this->assertTrue($this->get($this->testhost.'authorslist/0/'));
		$this->assertText('Eichendorff, Joseph von');
		$this->clickLink('Eichendorff, Joseph von 1');
		$this->assertResponse(200);
		$this->assertEqual($this->testhost.'authors/5/0/', $this->getUrl());
	}

	/*
	Check tags overview.
	*/
	public function testTags() {
		$this->assertTrue($this->get($this->testhost.'tagslist/0/'));
		$this->assertTitle('BicBucStriim :: Tags');
	}

	/*
	Check the navigation from tags overview to tag detail
	 */
	public function testNavigationFromTagsOverviewToDetail() {
		$this->assertTrue($this->get($this->testhost.'tagslist/0/'));
		$this->clickLink('Belletristik & Literatur 1');
		$this->assertResponse(200);
		$this->assertEqual($this->testhost.'tags/10/0/', $this->getUrl());
	}


}
