<?php
set_include_path("tests");
require_once('lib/simpletest/autorun.php');

/*
Extended test for the functionality of the title details page.
 */
class TestOfTitleDetails extends WebTestCase {

	# The external integration test site
	private $testhost = 'http://localhost:8080/bbs/';

	/*
	Check the title details, the display of information
	 */
	public function testTitleDetails() {
		$this->assertTrue($this->get($this->testhost.'titles/3/'));
		$this->assertTitle('BicBucStriim :: Book Details');	
		$this->assertText('Der seltzame Springinsfeld');
		$this->assertText('Hans Jakob Christoffel von Grimmelshausen, 2012');
		$this->assertLink('EPUB');		
		$this->assertPattern('/<img src="\/bbs\/titles\/3\/cover\/"/');
		$this->assertLink('Hans Jakob Christoffel von Grimmelshausen');		
		$this->assertLink('Fachbücher');		
		$this->assertText('Series');
		$this->assertLink('Serie Grimmelshausen');		
	}

	/*
	Check for no series
	 */
	public function testTitleDetailsNoSeries() {
		$this->assertTrue($this->get($this->testhost.'titles/6/'));
		$this->assertTitle('BicBucStriim :: Book Details');	
		$this->assertText('Neues Leben');
		$this->assertNoText('Series');
	}

	/*
	Check existence of cover image
	 */
	public function testCheckCoverImage(){
		$this->assertTrue($this->get($this->testhost.'titles/3/cover/'));
		$this->assertResponse(200);
		$this->assertMime('image/jpeg;base64');
	}

	/*
	Check the navigation from title detail to author details
	 */
	public function testNavigationFromTitleToAuthor() {
		$author = 'Hans Jakob Christoffel von Grimmelshausen';
		$this->assertTrue($this->get($this->testhost.'titles/3/'));
		$this->clickLink($author);
		$this->assertResponse(200);
		$this->assertEqual($this->testhost.'authors/6/', $this->getUrl());
		$this->assertTitle('BicBucStriim :: Author Details');	
		$this->assertText('Books by '.$author);
	}

	/*
	Check the navigation from title detail to series details
	 */
	public function testNavigationFromTitleToSeries() {
		$series = 'Serie Grimmelshausen';
		$this->assertTrue($this->get($this->testhost.'titles/3/'));
		$this->clickLink($series);
		$this->assertResponse(200);
		$this->assertEqual($this->testhost.'series/1/', $this->getUrl());
		$this->assertTitle('BicBucStriim :: Series Details');	
		$this->assertText('Books in series "'.$series.'"');
	}

	/*
	Check the navigation from title detail to tag details
	 */
	public function testNavigationFromTitleToTag() {
		$tag = 'Fachbücher';
		$this->assertTrue($this->get($this->testhost.'titles/3/'));
		$this->clickLink($tag);
		$this->assertResponse(200);
		$this->assertEqual($this->testhost.'tags/3/', $this->getUrl());
		$this->assertTitle('BicBucStriim :: Tag Details');	
		$this->assertText('Books tagged with "'.$tag.'"');
	}

	/*
	Check book download without password challenge or download cookie
	 */
	public function testDownloadBook() {
		$this->assertTrue($this->get($this->testhost.'titles/3/file/Der+seltzame+Springinsfeld+-+Hans+Jakob+Christoffel+von+Grimmelshausen.epub'));
		$this->assertResponse(200);
		$this->assertMime('application/epub+zip');		
		$this->assertTrue($this->get($this->testhost.'titles/2/file/Trutz+Simplex+-+Hans+Jakob+Christoffel+von+Grimmelshausen.mobi'));
		$this->assertResponse(200);
		$this->assertMime('application/x-mobipocket-ebook');				
	}

	/*
	Check book download for not exisiting file
	 */
	public function testDownloadNotExisitingBook() {
		$this->get($this->testhost.'titles/999/file/Der+seltzame+Springinsfeld+-+Hans+Jakob+Christoffel+von+Grimmelshausen.epub');
		$this->assertResponse(404);
	}	
}
?>
