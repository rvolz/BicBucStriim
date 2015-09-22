<?php
set_include_path("tests:vendor");
require_once 'simpletest/simpletest/autorun.php';
require_once 'rb.php';
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
	    $this->bbs = new BicBucStriim(self::DATADB,false);
	}

	function tearDown() {
		// Must use nuke() to clear caches etc.
		R::nuke();
		R::close();
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
		$this->assertEqual(1, count($configs));

		$configA = array('propa' => 'vala', 'propb' => 1);
		$this->bbs->saveConfigs($configA);
		$configs = $this->bbs->configs();
		$this->assertEqual(3, count($configs));
		$this->assertEqual('propa', $configs[2]->name);
		$this->assertEqual('vala', $configs[2]->val);
		$this->assertEqual('propb', $configs[3]->name);
		$this->assertEqual(1, $configs[3]->val);

		$configB = array('propa' => 'vala', 'propb' => 2);
		$this->bbs->saveConfigs($configB);
		$configs = $this->bbs->configs();
		$this->assertEqual(3, count($configs));
		$this->assertEqual('propa', $configs[2]->name);
		$this->assertEqual('vala', $configs[2]->val);
		$this->assertEqual('propb', $configs[3]->name);
		$this->assertEqual(2, $configs[3]->val);
	}

	function testAddUser() {
		$this->assertEqual(1, count($this->bbs->users()));
		$user = $this->bbs->addUser('testuser', 'testuser');
		$this->assertNotNull($user);
		$this->assertEqual('testuser', $user->username);
		$this->assertNotEqual('testuser', $user->password);
		$this->assertNull($user->tags);
		$this->assertNull($user->languages);
		$this->assertEqual(0, $user->role);
	}

	function testAddUserEmptyUser() {
		$user = $this->bbs->addUser('', '');
		$this->assertNull($user);
	}

	function testAddUserEmptyUsername() {
		$user = $this->bbs->addUser('testuser2', '');
		$this->assertNull($user);
	}

	function testAddUserEmptyPassword() {
		$user = $this->bbs->addUser('', '');
		$this->assertNull($user);
	}

	function testGetUser() {
		$this->bbs->addUser('testuser', 'testuser');
		$this->bbs->addUser('testuser2', 'testuser2');
		$this->assertEqual(3, count($this->bbs->users()));
		$user = $this->bbs->user(3);
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
		$this->assertEqual(3, count($this->bbs->users()));

		$deleted = $this->bbs->deleteUser(1);
		$this->assertFalse($deleted);

		$deleted = $this->bbs->deleteUser(100);
		$this->assertFalse($deleted);

		$deleted = $this->bbs->deleteUser(3);
		$this->assertTrue($deleted);
		$this->assertEqual(2, count($this->bbs->users()));
		$user = $this->bbs->user(2);
		$this->assertNotNull($user);
		$this->assertEqual('testuser', $user->username);
	}

	function testChangeUser() {
		$this->bbs->addUser('testuser', 'testuser');
		$this->bbs->addUser('testuser2', 'testuser2');
		$users = $this->bbs->users();
		$password2 = $users[3]->password;

		$changed = $this->bbs->changeUser(3, $password2, 'deu', 'poetry', 'user');
		$this->assertEqual($password2, $changed->password);
		$this->assertEqual('deu', $changed->languages);
		$this->assertEqual('poetry', $changed->tags);

		$changed = $this->bbs->changeUser(3, 'new password', 'deu', 'poetry', 'user');
		$this->assertNotEqual($password2, $changed->password);
		$this->assertEqual('deu', $changed->languages);
		$this->assertEqual('poetry', $changed->tags);

		$changed = $this->bbs->changeUser(3, '', 'deu', 'poetry', 'user');
		$this->assertNull($changed);
	}

	function testChangeUserRole() {
		$this->bbs->addUser('testuser', 'testuser');
		$this->bbs->addUser('testuser2', 'testuser2');
		$users = $this->bbs->users();
		$password2 = $users[3]->password;

		$this->assertEqual('0', $users[3]->role);
		$changed = $this->bbs->changeUser(3, $password2, 'deu', 'poetry', 'admin');
		$this->assertEqual('1', $changed->role);
		$changed = $this->bbs->changeUser(3, '', 'deu', 'poetry', 'admin');
		$this->assertNull($changed);
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
		$this->assertEqual(0, $result->refctr);
		$result2 = $this->bbs->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, 1);
		$this->assertEqual('Author 1', $result2->cname);
		$this->assertEqual(0, $result2->refctr);
	}

	function testEditAuthorThumbnail() {				
		$this->assertTrue($this->bbs->editAuthorThumbnail(1, 'Author Name', true, 'tests/fixtures/author1.jpg', 'image/jpeg'));
		$this->assertTrue(file_exists(self::DATA.'/authors/author_1_thm.png'));
		$result2 = $this->bbs->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, 1);
		$this->assertEqual('Author Name', $result2->cname);
		$this->assertEqual(1, $result2->refctr);
		$artefacts = $result2->ownArtefact;
		$this->assertEqual(1, count($artefacts));
		$result = $artefacts[1];
		$this->assertNotNull($result);
		$this->assertEqual(DataConstants::AUTHOR_THUMBNAIL_ARTEFACT, $result->atype);
		$this->assertEqual(self::DATA.'/authors/author_1_thm.png', $result->url);
	}

	function testGetAuthorThumbnail() {				
		$this->assertTrue($this->bbs->editAuthorThumbnail(1, 'Author Name', true, 'tests/fixtures/author1.jpg', 'image/jpeg'));
		$this->assertTrue($this->bbs->editAuthorThumbnail(2, 'Author Name', true, 'tests/fixtures/author1.jpg', 'image/jpeg'));
		$result = $this->bbs->getAuthorThumbnail(1);
		$this->assertNotNull($result);
		$this->assertEqual(DataConstants::AUTHOR_THUMBNAIL_ARTEFACT, $result->atype);
		$this->assertEqual(self::DATA.'/authors/author_1_thm.png', $result->url);
		$result = $this->bbs->getAuthorThumbnail(2);
		$this->assertNotNull($result);
	}

	function testDeleteAuthorThumbnail() {				
		$this->assertTrue($this->bbs->editAuthorThumbnail(1, 'Author Name', true, 'tests/fixtures/author1.jpg', 'image/jpeg'));
		$this->assertNotNull($this->bbs->getAuthorThumbnail(1));
		$this->assertTrue($this->bbs->deleteAuthorThumbnail(1));
		$this->assertFalse(file_exists(self::DATA.'/authors/author_1_thm.png'));
		$this->assertNull($this->bbs->getAuthorThumbnail(1));
		$this->assertEqual(0, R::count('artefact'));
		$this->assertEqual(0, R::count('calibrething'));
	}

	function testAuthorLinks() {
		$this->assertEqual(0, count($this->bbs->authorLinks(1)));
		$this->bbs->addAuthorLink(2, 'Author 1', 'google', 'http://google.com/1');
		$this->bbs->addAuthorLink(1, 'Author 2', 'amazon', 'http://amazon.com/2');
		$links = $this->bbs->authorLinks(1);
		$this->assertEqual(2, R::count('link'));
		$this->assertEqual(1, count($links));
		$this->assertEqual(DataConstants::AUTHOR_LINK, $links[0]->ltype);
		$this->assertEqual('amazon', $links[0]->label);
		$this->assertEqual('http://amazon.com/2', $links[0]->url);
		$this->assertEqual(2, $links[0]->id);
		$this->assertTrue($this->bbs->deleteAuthorLink(1, 2));
		$this->assertEqual(0, count($this->bbs->authorLinks(1)));
		$this->assertEqual(1, R::count('link'));
	}

	function testAuthorNote() {
		$this->assertNull($this->bbs->authorNote(1));
		$this->bbs->editAuthorNote(2, 'Author 1', 'text/plain', 'Goodbye, goodbye!');
		$this->bbs->editAuthorNote(1, 'Author 2', 'text/plain', 'Hello again!');
		$this->assertEqual(2, R::count('note'));
		$note = $this->bbs->authorNote(1);
		$this->assertNotNull($note);
		$this->assertEqual(DataConstants::AUTHOR_NOTE, $note->ntype);
		$this->assertEqual('text/plain', $note->mime);
		$this->assertEqual('Hello again!', $note->ntext);
		$this->assertEqual(2, $note->id);		
		$note = $this->bbs->editAuthorNote(1, 'Author 2', 'text/markdown', '*Hello again!*');
		$this->assertEqual('text/markdown', $note->mime);
		$this->assertEqual('*Hello again!*', $note->ntext);
		$this->assertTrue($this->bbs->deleteAuthorNote(1, 2));
		$this->assertEqual(1, R::count('note'));
	}

	function testIsTitleThumbnailAvailable() {
		$this->assertNotNull($this->bbs->titleThumbnail(1, 'tests/fixtures/author1.jpg', true));
		$this->assertTrue($this->bbs->isTitleThumbnailAvailable(1));
		$this->assertFalse($this->bbs->isTitleThumbnailAvailable(2));
	}


	function testClearThumbnail() {
		$result = $this->bbs->titleThumbnail(3, 'tests/fixtures/author1.jpg', true);
		$this->assertNotNull($result);
		$this->assertTrue($this->bbs->isTitleThumbnailAvailable(3));
		$this->assertTrue($this->bbs->clearThumbnails());
		clearstatcache(true);
		$this->assertFalse(file_exists($result));
	}

}
?>

