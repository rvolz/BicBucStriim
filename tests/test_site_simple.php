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
	Just after installation the home page is redirected 
	to the admin page for configuration
	*/
	public function testInitialRedirect() {
		$this->setMaximumRedirects(0);
		$this->assertTrue($this->get($this->testhost));
		$this->assertResponse(302);
		$this->setMaximumRedirects(2);
		$this->assertTrue($this->get($this->testhost));
		$this->assertResponse(200);
		$this->assertEqual($this->testhost.'admin/', $this->getUrl());
		$this->assertTitle('BicBucStriim :: Configuration');
	}

	/*
	Only enter the path to the Calibre library. 
	No admin or download password set.
	If the calibre dir does not exists, show an error, else svae the settings.
	*/
	public function testAdminCalibreDir() {
		$this->assertTrue($this->get($this->testhost));
		$this->setField('calibre_dir', '/tmp/no_calibre_library');
		$this->clickSubmit('Save');
		$this->assertResponse(200);
		$this->assertText('The configured Calibre directory cannot be used');

		$this->assertTrue($this->get($this->testhost));
		$this->setField('calibre_dir', '/tmp/calibre');
		$this->clickSubmit('Save');
		$this->assertResponse(200);
		$this->assertText('Changes saved');		
	}

	/*
	If a valid calibre dir is saved the start page shows the last 30 books.
	*/
	public function testStartPage() {
		$this->assertTrue($this->get($this->testhost));
		$this->assertResponse(200);
		$this->assertTitle('BicBucStriim :: Most recent 30');	
		$this->assertLink('The Stones of Venice, Volume II (2012)');
		$this->assertLink('Lob der Faulheit (2012)');
	}

	/*
	No admin password set. Access admin page without password challenge or
	admin access cookie.
	*/
	public function testAccessAdminPage() {
		$this->assertNoCookie('admin_access');
		$this->assertTrue($this->get($this->testhost.'admin/'));
		$this->assertResponse(200);
		$this->assertTitle('BicBucStriim :: Configuration');
		$this->assertField('calibre_dir', '/tmp/calibre');		
	}

	/*
	Check titles overview.
	*/
	public function testTitles() {
		$this->assertTrue($this->get($this->testhost.'titles/'));
		$this->assertTitle('BicBucStriim :: Books');
	}

	/*
	Check the navigation from titles overview to title detail and back
	 */
	public function testNavigationFromTitlesOverviewToDetail() {
		$this->assertTrue($this->get($this->testhost.'titles/'));
		$this->clickLink('seltzame Springinsfeld, Der');
		$this->assertResponse(200);
		$this->assertEqual($this->testhost.'titles/3/', $this->getUrl());
	}

	/*
	Check thumbnail generation for titles overview
	 */
	public function testTitleThumbnails() {
		$this->assertTrue($this->get($this->testhost.'titles/'));
		$this->assertPattern('/<img src="\/bbs\/titles\/3\/thumbnail\/"/');
		$this->assertTrue($this->get($this->testhost.'titles/3/thumbnail/'));
		$this->assertResponse(200);
		$this->assertMime('image/jpeg;base64');		
	}

	/*
	Check authors overview.
	*/
	public function testAuthors() {
		$this->assertTrue($this->get($this->testhost.'authors/'));
		$this->assertTitle('BicBucStriim :: Authors');
	}

	/*
	Check the navigation from authors overview to author detail 
	 */
	public function testNavigationFromAuthorsOverviewToDetail() {
		$this->assertTrue($this->get($this->testhost.'authors/'));
		$this->assertText('Heyse, Paul');
		$this->clickLink('Heyse, Paul 1');
		$this->assertResponse(200);
		$this->assertEqual($this->testhost.'authors/8/', $this->getUrl());
	}

	/*
	Check tags overview.
	*/
	public function testTags() {
		$this->assertTrue($this->get($this->testhost.'tags/'));
		$this->assertTitle('BicBucStriim :: Tags');
	}

	/*
	Check the navigation from tags overview to tag detail
	 */
	public function testNavigationFromTagsOverviewToDetail() {
		$this->assertTrue($this->get($this->testhost.'tags/'));
		$this->clickLink('Biografien & Memoiren 1');
		$this->assertResponse(200);
		$this->assertEqual($this->testhost.'tags/4/', $this->getUrl());
	}


}
