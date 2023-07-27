<?php

namespace Tests\Domain\BicBucStriim;

use App\Domain\BicBucStriim\BicBucStriim;
use App\Domain\BicBucStriim\DataConstants;
use PHPUnit\Framework\TestCase;
use RedBeanPHP\R;

class BicBucStriimTest extends TestCase
{
    public const SCHEMA = __DIR__ . '/../../../data/schema.sql';
    public const TESTSCHEMA = __DIR__ . '/../../data/schema.sql';
    public const DB2 = __DIR__ . '/../../fixtures/data2.db';

    public const DATA = __DIR__ . '/../../data';
    public const DATADB = __DIR__ . '/../../data/data.db';

    public $bbs;

    public function setUp(): void
    {
        if (file_exists(self::DATA)) {
            system("rm -rf ".self::DATA);
        }
        mkdir(self::DATA);
        chmod(self::DATA, 0777);
        copy(self::DB2, self::DATADB);
        copy(self::SCHEMA, self::TESTSCHEMA);
        $this->bbs = new BicBucStriim(self::DATADB, false);
    }

    public function tearDown(): void
    {
        // Must use nuke() to clear caches etc.
        R::nuke();
        R::close();
        $this->bbs = null;
        system("rm -rf ".self::DATA);
    }

    public function testDbOk()
    {
        $this->assertTrue($this->bbs->dbOk());
        $this->bbs = new BicBucStriim(self::DATA.'/nodata.db');
        $this->assertFalse($this->bbs->dbOk());
    }

    public function testCreateDb()
    {
        $this->bbs = new BicBucStriim(self::DATA.'/nodata.db');
        $this->assertFalse($this->bbs->dbOk());
        $this->bbs->createDataDB(self::DATA.'/newdata.db');
        $this->assertTrue(file_exists(self::DATA.'/newdata.db'));
        $this->bbs = new BicBucStriim(self::DATA.'/newdata.db');
        $this->assertTrue($this->bbs->dbOk());
    }

    public function testConfigs()
    {
        $configs = $this->bbs->configs();
        $this->assertCount(1, $configs);

        $configA = ['propa' => 'vala', 'propb' => 1];
        $this->bbs->saveConfigs($configA);
        $configs = $this->bbs->configs();
        $this->assertCount(3, $configs);
        $this->assertEquals('propa', $configs[2]->name);
        $this->assertEquals('vala', $configs[2]->val);
        $this->assertEquals('propb', $configs[3]->name);
        $this->assertEquals(1, $configs[3]->val);

        $configB = ['propa' => 'vala', 'propb' => 2];
        $this->bbs->saveConfigs($configB);
        $configs = $this->bbs->configs();
        $this->assertCount(3, $configs);
        $this->assertEquals('propa', $configs[2]->name);
        $this->assertEquals('vala', $configs[2]->val);
        $this->assertEquals('propb', $configs[3]->name);
        $this->assertEquals(2, $configs[3]->val);
    }

    public function testAddUser()
    {
        $this->assertCount(1, $this->bbs->users());
        $user = $this->bbs->addUser('testuser', 'testuser');
        $this->assertNotNull($user);
        $this->assertEquals('testuser', $user->username);
        $this->assertNotEquals('testuser', $user->password);
        $this->assertNull($user->tags);
        $this->assertNull($user->languages);
        $this->assertEquals(0, $user->role);
    }

    public function testAddUserEmptyUser()
    {
        $user = $this->bbs->addUser('', '');
        $this->assertNull($user);
    }

    public function testAddUserEmptyUsername()
    {
        $user = $this->bbs->addUser('testuser2', '');
        $this->assertNull($user);
    }

    public function testAddUserEmptyPassword()
    {
        $user = $this->bbs->addUser('', '');
        $this->assertNull($user);
    }

    public function testGetUser()
    {
        $this->bbs->addUser('testuser', 'testuser');
        $this->bbs->addUser('testuser2', 'testuser2');
        $this->assertCount(3, $this->bbs->users());
        $user = $this->bbs->user(3);
        $this->assertNotNull($user);
        $this->assertEquals('testuser2', $user->username);
        $this->assertNotEquals('testuser2', $user->password);
        $this->assertNull($user->tags);
        $this->assertNull($user->languages);
        $this->assertEquals(0, $user->role);
    }

    public function testDeleteUser()
    {
        $this->bbs->addUser('testuser', 'testuser');
        $this->bbs->addUser('testuser2', 'testuser2');
        $this->assertCount(3, $this->bbs->users());

        $deleted = $this->bbs->deleteUser(1);
        $this->assertFalse($deleted);

        $deleted = $this->bbs->deleteUser(100);
        $this->assertFalse($deleted);

        $deleted = $this->bbs->deleteUser(3);
        $this->assertTrue($deleted);
        $this->assertCount(2, $this->bbs->users());
        $user = $this->bbs->user(2);
        $this->assertNotNull($user);
        $this->assertEquals('testuser', $user->username);
    }

    public function testChangeUser()
    {
        $this->bbs->addUser('testuser', 'testuser');
        $this->bbs->addUser('testuser2', 'testuser2');
        $users = $this->bbs->users();
        $password2 = $users[3]->password;

        $changed = $this->bbs->changeUser(3, $password2, 'deu', 'poetry', 'user');
        $this->assertEquals($password2, $changed->password);
        $this->assertEquals('deu', $changed->languages);
        $this->assertEquals('poetry', $changed->tags);

        $changed = $this->bbs->changeUser(3, 'new password', 'deu', 'poetry', 'user');
        $this->assertNotEquals($password2, $changed->password);
        $this->assertEquals('deu', $changed->languages);
        $this->assertEquals('poetry', $changed->tags);

        $changed = $this->bbs->changeUser(3, '', 'deu', 'poetry', 'user');
        $this->assertNull($changed);
    }

    public function testChangeUserRole()
    {
        $this->bbs->addUser('testuser', 'testuser');
        $this->bbs->addUser('testuser2', 'testuser2');
        $users = $this->bbs->users();
        $password2 = $users[3]->password;

        $this->assertEquals('0', $users[3]->role);
        $changed = $this->bbs->changeUser(3, $password2, 'deu', 'poetry', 'admin');
        $this->assertEquals('1', $changed->role);
        $changed = $this->bbs->changeUser(3, '', 'deu', 'poetry', 'admin');
        $this->assertNull($changed);
    }

    public function testIdTemplates()
    {
        $this->assertCount(0, $this->bbs->idTemplates());
        $this->bbs->addIdTemplate('google', 'http://google.com/%id%', 'Google search');
        $this->bbs->addIdTemplate('amazon', 'http://amazon.com/%id%', 'Amazon search');
        $this->assertCount(2, $this->bbs->idTemplates());
        $template = $this->bbs->idTemplate('amazon');
        $this->assertEquals('amazon', $template->name);
        $this->assertEquals('http://amazon.com/%id%', $template->val);
        $this->assertEquals('Amazon search', $template->label);
    }

    public function testDeleteIdTemplates()
    {
        $this->assertCount(0, $this->bbs->idTemplates());
        $this->bbs->addIdTemplate('google', 'http://google.com/%id%', 'Google search');
        $this->bbs->addIdTemplate('amazon', 'http://amazon.com/%id%', 'Amazon search');
        $this->assertCount(2, $this->bbs->idTemplates());
        $this->bbs->deleteIdTemplate('amazon123');
        $this->assertCount(2, $this->bbs->idTemplates());
        $this->bbs->deleteIdTemplate('amazon');
        $this->assertCount(1, $this->bbs->idTemplates());
    }

    public function testChangeIdTemplate()
    {
        $this->assertCount(0, $this->bbs->idTemplates());
        $this->bbs->addIdTemplate('google', 'http://google.com/%id%', 'Google search');
        $this->bbs->addIdTemplate('amazon', 'http://amazon.com/%id%', 'Amazon search');
        $this->assertCount(2, $this->bbs->idTemplates());
        $template = $this->bbs->idTemplate('amazon');
        $this->assertEquals('amazon', $template->name);
        $this->assertEquals('http://amazon.com/%id%', $template->val);
        $this->assertEquals('Amazon search', $template->label);
        $template = $this->bbs->changeIdTemplate('amazon', 'http://amazon.de/%id%', 'Amazon DE search');
        $this->assertEquals('amazon', $template->name);
        $this->assertEquals('http://amazon.de/%id%', $template->val);
        $this->assertEquals('Amazon DE search', $template->label);
    }

    public function testCalibreThing()
    {
        $this->assertNull($this->bbs->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, 1));
        $result = $this->bbs->addCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, 1, 'Author 1');
        $this->assertNotNull($result);
        $this->assertEquals('Author 1', $result->cname);
        $this->assertEquals(0, $result->refctr);
        $result2 = $this->bbs->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, 1);
        $this->assertEquals('Author 1', $result2->cname);
        $this->assertEquals(0, $result2->refctr);
    }

    public function testEditAuthorThumbnail()
    {
        $this->assertTrue($this->bbs->editAuthorThumbnail(1, 'Author Name', true, 'tests/fixtures/author1.jpg', 'image/jpeg'));
        $this->assertTrue(file_exists(self::DATA.'/authors/author_1_thm.png'));
        $result2 = $this->bbs->getCalibreThing(DataConstants::CALIBRE_AUTHOR_TYPE, 1);
        $this->assertEquals('Author Name', $result2->cname);
        $this->assertEquals(1, $result2->refctr);
        $artefacts = $result2->ownArtefact;
        $this->assertCount(1, $artefacts);
        $result = $artefacts[1];
        $this->assertNotNull($result);
        $this->assertEquals(DataConstants::AUTHOR_THUMBNAIL_ARTEFACT, $result->atype);
        $this->assertEquals(self::DATA.'/authors/author_1_thm.png', $result->url);
    }

    public function testGetAuthorThumbnail()
    {
        $this->assertTrue($this->bbs->editAuthorThumbnail(1, 'Author Name', true, 'tests/fixtures/author1.jpg', 'image/jpeg'));
        $this->assertTrue($this->bbs->editAuthorThumbnail(2, 'Author Name', true, 'tests/fixtures/author1.jpg', 'image/jpeg'));
        $result = $this->bbs->getAuthorThumbnail(1);
        $this->assertNotNull($result);
        $this->assertEquals(DataConstants::AUTHOR_THUMBNAIL_ARTEFACT, $result->atype);
        $this->assertEquals(self::DATA.'/authors/author_1_thm.png', $result->url);
        $result = $this->bbs->getAuthorThumbnail(2);
        $this->assertNotNull($result);
    }

    public function testDeleteAuthorThumbnail()
    {
        $this->assertTrue($this->bbs->editAuthorThumbnail(1, 'Author Name', true, 'tests/fixtures/author1.jpg', 'image/jpeg'));
        $this->assertNotNull($this->bbs->getAuthorThumbnail(1));
        $this->assertTrue($this->bbs->deleteAuthorThumbnail(1));
        $this->assertFalse(file_exists(self::DATA.'/authors/author_1_thm.png'));
        $this->assertNull($this->bbs->getAuthorThumbnail(1));
        $this->assertEquals(0, R::count('artefact'));
        $this->assertEquals(0, R::count('calibrething'));
    }

    public function testAuthorLinks()
    {
        $this->assertCount(0, $this->bbs->authorLinks(1));
        $this->bbs->addAuthorLink(2, 'Author 1', 'google', 'http://google.com/1');
        $this->bbs->addAuthorLink(1, 'Author 2', 'amazon', 'http://amazon.com/2');
        $links = $this->bbs->authorLinks(1);
        $this->assertEquals(2, R::count('link'));
        $this->assertCount(1, $links);
        $this->assertEquals(DataConstants::AUTHOR_LINK, $links[0]->ltype);
        $this->assertEquals('amazon', $links[0]->label);
        $this->assertEquals('http://amazon.com/2', $links[0]->url);
        $this->assertEquals(2, $links[0]->id);
        $this->assertTrue($this->bbs->deleteAuthorLink(1, 2));
        $this->assertCount(0, $this->bbs->authorLinks(1));
        $this->assertEquals(1, R::count('link'));
    }

    public function testAuthorNote()
    {
        $this->assertNull($this->bbs->authorNote(1));
        $this->bbs->editAuthorNote(2, 'Author 1', 'text/plain', 'Goodbye, goodbye!');
        $this->bbs->editAuthorNote(1, 'Author 2', 'text/plain', 'Hello again!');
        $this->assertEquals(2, R::count('note'));
        $note = $this->bbs->authorNote(1);
        $this->assertNotNull($note);
        $this->assertEquals(DataConstants::AUTHOR_NOTE, $note->ntype);
        $this->assertEquals('text/plain', $note->mime);
        $this->assertEquals('Hello again!', $note->ntext);
        $this->assertEquals(2, $note->id);
        $note = $this->bbs->editAuthorNote(1, 'Author 2', 'text/markdown', '*Hello again!*');
        $this->assertEquals('text/markdown', $note->mime);
        $this->assertEquals('*Hello again!*', $note->ntext);
        $this->assertTrue($this->bbs->deleteAuthorNote(1));
        $this->assertEquals(1, R::count('note'));
    }

    public function testIsTitleThumbnailAvailable()
    {
        $this->assertNotNull($this->bbs->titleThumbnail(1, 'tests/fixtures/author1.jpg', true));
        $this->assertTrue($this->bbs->isTitleThumbnailAvailable(1));
        $this->assertFalse($this->bbs->isTitleThumbnailAvailable(2));
    }


    public function testClearThumbnail()
    {
        $result = $this->bbs->titleThumbnail(3, 'tests/fixtures/author1.jpg', true);
        $this->assertNotNull($result);
        $this->assertTrue($this->bbs->isTitleThumbnailAvailable(3));
        $this->assertTrue($this->bbs->clearThumbnails());
        clearstatcache(true);
        $this->assertFalse(file_exists($result));
    }
}
