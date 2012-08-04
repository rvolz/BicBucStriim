<?php
set_include_path("tests");
require_once('lib/simpletest/autorun.php');
require_once('lib/simpletest/web_tester.php');

/*
Test the global download protection for books. The test case turns on 
global download protection and then tries to download books.
 */
class TestOfGlobalDownloadProtection extends WebTestCase {

	# The external integration test site
	private $testhost = 'http://localhost:8080/bbs/';

	/*
	Turn on global download protection with separate password, 
	not via admin password
	 */
	public function testTurnOnProtectionWithSeparatePw() {
		$this->assertNoCookie('admin_access');
		$this->assertNoCookie('glob_dl_access');
		$this->assertTrue($this->get($this->testhost.'admin'));
		$this->setField('glob_dl_choice', 2);
		$this->setField('glob_dl_password', 'abc');
		$this->clickSubmit('Save');
		$this->assertResponse(200);
		$this->assertText('Changes saved');				
	}

	/*
	Check direct book download without password challenge or download cookie
	 */
	public function testDownloadBookWithoutPermission() {
		$this->assertNoCookie('glob_dl_access');
		$this->get($this->testhost.'titles/3/file/Der+seltzame+Springinsfeld+-+Hans+Jakob+Christoffel+von+Grimmelshausen.epub');
		$this->assertResponse(401);
		$this->get($this->testhost.'titles/2/file/Trutz+Simplex+-+Hans+Jakob+Christoffel+von+Grimmelshausen.mobi');
		$this->assertResponse(401);
	}

	/*
	Check direct book download with global download cookie
	 */
	public function testDownloadBookWithCookie() {
		$this->assertNoCookie('glob_dl_access');
		$this->setCookie('glob_dl_access','abc');
		$this->get($this->testhost.'titles/3/file/Der+seltzame+Springinsfeld+-+Hans+Jakob+Christoffel+von+Grimmelshausen.epub');
		$this->assertResponse(200);
	}


}
?>
