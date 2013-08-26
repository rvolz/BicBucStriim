<?php
set_include_path("tests");
require_once 'lib/simpletest/autorun.php';
require_once 'vendor/rb.php';
require_once 'lib/BicBucStriim/data_constants.php';
require_once 'lib/BicBucStriim/calibre_thing.php';
require_once 'lib/BicBucStriim/bicbucstriim.php';
require_once 'vendor/ircmaxell/password-compat/lib/password.php';
class TestOfBicBucStriim extends UnitTestCase {

	const SCHEMA = './data/schema.sql';
	const TESTSCHEMA = './tests/data/schema.sql';
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
	    copy(self::SCHEMA, self::TESTSCHEMA);
	    $this->bbs = new BicBucStriim(self::DATADB);
	}

	function tearDown() {
		// Must use nuke() to clear caches etc.
		R::nuke();
		$this->bbs = NULL;
		system("rm -rf ".self::DATA);
	}

	function testDbOk() {
		$this->assertTrue($this->bbs->dbOk());
		$this->bbs = new BicBucStriim(self::DATA.'/nodata.db');	
		$this->assertFalse($this->bbs->dbOk());
	}

	function testCreateDb() {
		$this->bbs = new BicBucStriim(self::DATA.'/nodata.db');	
		$this->assertFalse($this->bbs->dbOk());
		$this->bbs->createDataDB(self::DATA.'/newdata.db');
		$this->assertTrue(file_exists(self::DATA.'/newdata.db'));
		$this->bbs = new BicBucStriim(self::DATA.'/newdata.db');	
		$this->assertTrue($this->bbs->dbOk());
	}

	function testConfigs() {
		$configs = $this->bbs->configs();
		$this->assertEqual(0, count($configs));

		$configA = array('propa' => 'vala', 'propb' => 1);
		$this->bbs->saveConfigs($configA);
		$configs = $this->bbs->configs();
		$this->assertEqual(2, count($configs));
		$this->assertEqual('propa', $configs[1]->name);
		$this->assertEqual('vala', $configs[1]->val);
		$this->assertEqual('propb', $configs[2]->name);
		$this->assertEqual(1, $configs[2]->val);

		$configB = array('propa' => 'vala', 'propb' => 2);
		$this->bbs->saveConfigs($configB);
		$configs = $this->bbs->configs();
		$this->assertEqual(2, count($configs));
		$this->assertEqual('propa', $configs[1]->name);
		$this->assertEqual('vala', $configs[1]->val);
		$this->assertEqual('propb', $configs[2]->name);
		$this->assertEqual(2, $configs[2]->val);
	}

	function testAddUser() {
		$this->assertEqual(0, count($this->bbs->users()));
		$user = $this->bbs->addUser('testuser', 'testuser');
		$this->assertNotNull($user);
		$this->assertEqual('testuser', $user->username);
		$this->assertNotEqual('testuser', $user->password);
		$this->assertNull($user->tags);
		$this->assertNull($user->languages);
		$this->assertEqual(0, $user->role);
		echo var_export($user->to_json(),true);
		echo var_export($user->getProperties(),true);
	}

	function testGetUser() {
		$this->bbs->addUser('testuser', 'testuser');
		$this->bbs->addUser('testuser2', 'testuser2');
		$this->assertEqual(2, count($this->bbs->users()));
		$user = $this->bbs->user(2);
		$this->assertNotNull($user);
		$this->assertEqual('testuser2', $user->username);
		$this->assertNotEqual('testuser2', $user->password);
		$this->assertNull($user->tags);
		$this->assertNull($user->languages);
		$this->assertEqual(0, $user->role);
	}

	function testDeleteUser() {
		$this->bbs->addUser('testuser', 'testuser');
		$this->bbs->addUser('testuser2', 'testuser2');
		$this->assertEqual(2, count($this->bbs->users()));

		$deleted = $this->bbs->deleteUser(1);
		$this->assertFalse($deleted);

		$deleted = $this->bbs->deleteUser(100);
		$this->assertFalse($deleted);

		$deleted = $this->bbs->deleteUser(2);
		$this->assertTrue($deleted);
		$this->assertEqual(1, count($this->bbs->users()));
		$user = $this->bbs->user(1);
		$this->assertNotNull($user);
		$this->assertEqual('testuser', $user->username);
	}

	function testChangeUser() {
		$this->bbs->addUser('testuser', 'testuser');
		$this->bbs->addUser('testuser2', 'testuser2');
		$users = $this->bbs->users();
		$password2 = $users[2]->password;

		$changed = $this->bbs->changeUser(2, $password2, 'deu', 'poetry');
		$this->assertEqual($password2, $changed->password);
		$this->assertEqual('deu', $changed->languages);
		$this->assertEqual('poetry', $changed->tags);

		$changed = $this->bbs->changeUser(2, 'new password', 'deu', 'poetry');
		$this->assertNotEqual($password2, $changed->password);
		$this->assertEqual('deu', $changed->languages);
		$this->assertEqual('poetry', $changed->tags);
	}

	function testIdTemplates() {
		$this->assertEqual(0, count($this->bbs->idTemplates()));
		$this->bbs->addIdTemplate('google', 'http://google.com/%id%', 'Google search');
		$this->bbs->addIdTemplate('amazon', 'http://amazon.com/%id%', 'Amazon search');
		$this->assertEqual(2, count($this->bbs->idTemplates()));
		$template = $this->bbs->idTemplate('amazon');
		$this->assertEqual('amazon', $template->name);
		$this->assertEqual('http://amazon.com/%id%', $template->val);
		$this->assertEqual('Amazon search', $template->label);
	}

	function testDeleteIdTemplates() {
		$this->assertEqual(0, count($this->bbs->idTemplates()));
		$this->bbs->addIdTemplate('google', 'http://google.com/%id%', 'Google search');
		$this->bbs->addIdTemplate('amazon', 'http://amazon.com/%id%', 'Amazon search');
		$this->assertEqual(2, count($this->bbs->idTemplates()));
		$this->bbs->deleteIdTemplate('amazon123');
		$this->assertEqual(2, count($this->bbs->idTemplates()));
		$this->bbs->deleteIdTemplate('amazon');
		$this->assertEqual(1, count($this->bbs->idTemplates()));
	}

	function testChangeIdTemplate() {
		$this->assertEqual(0, count($this->bbs->idTemplates()));
		$this->bbs->addIdTemplate('google', 'http://google.com/%id%', 'Google search');
		$this->bbs->addIdTemplate('amazon', 'http://amazon.com/%id%', 'Amazon search');
		$this->assertEqual(2, count($this->bbs->idTemplates()));
		$template = $this->bbs->idTemplate('amazon');
		$this->assertEqual('amazon', $template->name);
		$this->assertEqual('http://amazon.com/%id%', $template->val);
		$this->assertEqual('Amazon search', $template->label);
		$template = $this->bbs->changeIdTemplate('amazon', 'http://amazon.de/%id%', 'Amazon DE search');
		$this->assertEqual('amazon', $template->name);
		$this->assertEqual('http://amazon.de/%id%', $template->val);
		$this->assertEqual('Amazon DE search', $template->label);
	}

	function testCalibreThing() {				
		$this->assertNull($this->bbs->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, 1));
		$result = $this->bbs->addCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, 1, 'Author 1');
		$this->assertNotNull($result);
		$this->assertEqual('Author 1', $result->cname);
		$result2 = $this->bbs->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, 1);
		$this->assertEqual('Author 1', $result2->cname);
	}

	function testEditAuthorThumbnail() {				
		$this->assertTrue($this->bbs->editAuthorThumbnail(1, 'Author Name', true, 'tests/fixtures/author1.jpg'));
		$this->assertTrue(file_exists(self::DATA.'/authors/author_1_thm.png'));
		$result2 = $this->bbs->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, 1);
		$this->assertEqual('Author Name', $result2->cname);
		$artefacts = $result2->ownArtefact;
		$this->assertEqual(1, count($artefacts));
		$result = $artefacts[1];
		$this->assertNotNull($result);
		$this->assertEqual(DataConstants::AUTHOR_THUMBNAIL_ARTEFACT, $result->atype);
		$this->assertEqual(self::DATA.'/authors/author_1_thm.png', $result->url);
	}

	function testGetAuthorThumbnail() {				
		$this->assertTrue($this->bbs->editAuthorThumbnail(1, 'Author Name', true, 'tests/fixtures/author1.jpg'));
		$result = $this->bbs->getAuthorThumbnail(1);
		$this->assertNotNull($result);
		$this->assertEqual(DataConstants::AUTHOR_THUMBNAIL_ARTEFACT, $result->atype);
		$this->assertEqual(self::DATA.'/authors/author_1_thm.png', $result->url);
	}

	function testDeleteAuthorThumbnail() {				
		$this->assertTrue($this->bbs->editAuthorThumbnail(1, 'Author Name', true, 'tests/fixtures/author1.jpg'));
		$this->assertNotNull($this->bbs->getAuthorThumbnail(1));
		$this->assertTrue($this->bbs->deleteAuthorThumbnail(1));
		$this->assertFalse(file_exists(self::DATA.'/authors/author_1_thm.png'));
		$this->assertNull($this->bbs->getAuthorThumbnail(1));
		$result2 = $this->bbs->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, 1);
		$artefacts = $result2->ownArtefact;
		$this->assertEqual(0, count($artefacts));
	}
}
?>

