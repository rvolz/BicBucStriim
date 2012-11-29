<?php
set_include_path("tests");
require_once('lib/simpletest/autorun.php');
require_once('lib/BicBucStriim/bicbucstriim.php');

class TestOfDbUpgrade extends UnitTestCase {

	const CDB1 = './tests/fixtures/metadata_empty.db';
	const CDB2 = './tests/fixtures/lib2/metadata.db';
	const CDB3 = './tests/fixtures/lib3/metadata.db';

	const DB2 = './tests/fixtures/data2.db';

	const DATA = './tests/data';
	const DATADB = './tests/data/data.db';

	var $bbs;

	function setUp() {
		if (file_exists(self::DATA))
			system("rm -rf ".self::DATA);	
    mkdir(self::DATA);
    chmod(self::DATA,0777);
    copy(self::DB2, self::DATADB);
    $this->bbs = new BicBucStriim(self::DATADB);
    $this->bbs->openCalibreDb(self::CDB2);
	}

	function tearDown() {
		$this->bbs = NULL;
		system("rm -rf ".self::DATA);
	}

	function getConfig($name) {
		$configs = $this->bbs->configs();
		foreach($configs as $config) {
			if ($config->{'name'} == $name) 
				return $config->{'val'};
		}
		return NULL;
	}

	##
	# Check the db upgrade by configuring a new key/value pair 
	# (INSERT) and an old one (UPDATE)
	# 
	function testDbUpgrade1to2() {
		$this->assertEqual('1', $this->getConfig('db_version'));
		$this->bbs->updateDbSchema1to2();		
		$this->assertEqual('2', $this->getConfig('db_version'));
		$c1 = new Config();
		$c1->name = 'test1';
		$c1->val = 'x';
		$c2 = new Config();
		$c2->name = 'calibre_dir';
		$c2->val = '/tmp';
		$this->bbs->saveConfigs(array($c1,$c2));		
		$this->assertEqual('x', $this->getConfig('test1'));
		$this->assertEqual('/tmp', $this->getConfig('calibre_dir'));
	}	
}
?>
