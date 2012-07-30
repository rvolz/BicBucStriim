<?php
set_include_path("tests");
require_once('lib/simpletest/autorun.php');

class TestsAll extends TestSuite {
	function TestsAll() {
		$this->TestSuite('All Tests');
		$this->addFile('test_bicbucstriim.php');
		$this->addFile('test_opds_generator.php');
	}
}
?>
