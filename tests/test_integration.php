<?php
set_include_path("tests");
require_once('lib/simpletest/autorun.php');
require_once('lib/simpletest/web_tester.php');

/*
These integration tests assume the existence of an external 
integration test environment. Run 'rake itest_up' before starting these tests.
 */
class TestsIntegration extends TestSuite {
	function TestsIntegration() {
		$this->TestSuite('All Integration Tests');
		$this->addFile('test_site_simple.php');
		$this->addFile('test_title_details.php');
		$this->addFile('test_author_details.php');
		$this->addFile('test_tag_details.php');
		$this->addFile('test_glob_dl_prot.php');
	}
}
?>
