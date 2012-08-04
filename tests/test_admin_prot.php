<?php
set_include_path("tests");
require_once('lib/simpletest/autorun.php');

/*
Test the admin page protection and the download protection for books. 
The test case uses a admin password, turns download on and then tries to download books.
 */
class TestOfAdminProtection extends WebTestCase {

	# The external integration test site
	private $testhost = 'http://localhost:8080/bbs/';

	/*
	Turn on global download protection with separate password, 
	not via admin password
	 */
	public function testTurnOnAdminProtection() {
		$this->assertNoCookie('admin_access');
		$this->assertNoCookie('glob_dl_access');
		$this->assertTrue($this->get($this->testhost.'admin'));
		$this->setField('glob_dl_choice', 1);
		$this->setField('admin_pw', 'def');
		$this->clickSubmit('Save');
		$this->assertResponse(200);
		$this->assertText('Changes saved');				
	}

	/*
	Check admin page access protection. 
	 */
	public function testAdminWithoutPermission()	{
		$this->assertNoCookie('admin_access');
		$access = $this->post($this->testhost.'admin/');
		$this->assertResponse(200);
		$this->assertEqual($this->testhost.'admin/', $this->getUrl());
		$this->assertText('Submit Password');
	}
	
	public function testAdminAccessWithoutPermission()	{
		$this->assertNoCookie('admin_access');
		$access = $this->post($this->testhost.'admin/access/check/');
		$this->assertResponse(200);
		$this->assertEqual($this->testhost.'admin/access/check/', $this->getUrl());
		$this->assertText('Invalid Password');
	}
	public function testAdminAccessWithPermission()	{
		$this->assertNoCookie('admin_access');
		$access = $this->post($this->testhost.'admin/access/check/', array('admin_pwin' => 'def'));
		$this->assertResponse(200);
		$this->assertEqual($this->testhost.'admin/', $this->getUrl());
		$this->assertText('Configuration');		
	}
	/*
	Check direct book download without password challenge or download cookie
	 */
	public function testDownloadBookWithoutPermission() {
		$this->assertNoCookie('glob_dl_access');
		$this->get($this->testhost.'titles/3/file/Der+seltzame+Springinsfeld+-+Hans+Jakob+Christoffel+von+Grimmelshausen.epub');
		$this->assertResponse(404);
		$this->get($this->testhost.'titles/2/file/Trutz+Simplex+-+Hans+Jakob+Christoffel+von+Grimmelshausen.mobi');
		$this->assertResponse(404);
	}

	/*
	Check direct book download with global download cookie
	 */
	public function testDownloadBookWithCookie() {
		$this->assertNoCookie('glob_dl_access');
		$this->setCookie('glob_dl_access','def');
		$this->get($this->testhost.'titles/3/file/Der+seltzame+Springinsfeld+-+Hans+Jakob+Christoffel+von+Grimmelshausen.epub');
		$this->assertResponse(200);
	}


	/*
	Check admin page without permission cookie
	 */
	public function testAccessAdminPageWithoutPermission() {
		$this->assertNoCookie('admin_access');
		$this->get($this->testhost.'admin');
		$this->assertResponse(200);
	}
}
?>
