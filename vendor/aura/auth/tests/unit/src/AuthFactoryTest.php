<?php
namespace Aura\Auth;

use PDO;
use Aura\Auth\Session\Session;
use Aura\Auth\Session\Segment;
use Aura\Auth\Verifier\FakeVerifier;

class AuthFactoryTest extends \PHPUnit_Framework_TestCase
{
    protected $factory;

    protected function setUp()
    {
        $this->factory = new AuthFactory($_COOKIE);
    }

    public function testNewAuth()
    {
        $auth = $this->factory->newInstance(array());
        $this->assertInstanceOf('Aura\Auth\Auth', $auth);
    }

    public function testNewAuthWithSessionAndSegment()
    {
        $auth = $this->factory->newInstance(array(), new Session(array()), new Segment);
        $this->assertInstanceOf('Aura\Auth\Auth', $auth);
    }

    public function testNewPdoAdapter_passwordVerifier()
    {
        $pdo = new PDO('sqlite::memory:');
        $adapter = $this->factory->newPdoAdapter(
            $pdo,
            1,
            array('username', 'password'),
            'accounts'
        );
        $this->assertInstanceOf('Aura\Auth\Adapter\PdoAdapter', $adapter);

        $verifier = $adapter->getVerifier();
        $this->assertInstanceOf('Aura\Auth\Verifier\PasswordVerifier',$verifier);
    }

    public function testNewPdoAdapter_customVerifier()
    {
        $pdo = new PDO('sqlite::memory:');
        $adapter = $this->factory->newPdoAdapter(
            $pdo,
            new FakeVerifier,
            array('username', 'password'),
            'accounts'
        );
        $this->assertInstanceOf('Aura\Auth\Adapter\PdoAdapter', $adapter);

        $verifier = $adapter->getVerifier();
        $this->assertInstanceOf('Aura\Auth\Verifier\FakeVerifier', $verifier);
    }

    public function testNewHtpasswdAdapter()
    {
        $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fake.htpasswd';
        $adapter = $this->factory->newHtpasswdAdapter($file);
        $this->assertInstanceOf('Aura\Auth\Adapter\HtpasswdAdapter', $adapter);

        $verifier = $adapter->getVerifier();
        $this->assertInstanceOf('Aura\Auth\Verifier\HtpasswdVerifier', $verifier);
    }

    public function testNewImapAdapter()
    {
        $adapter = $this->factory->newImapAdapter('imap.example.com', '{mbox}');
        $this->assertInstanceOf('Aura\Auth\Adapter\ImapAdapter', $adapter);
    }

    public function testNewLdapAdapter()
    {
        $adapter = $this->factory->newLdapAdapter('ldap.example.com', 'ou=Org');
        $this->assertInstanceOf('Aura\Auth\Adapter\LdapAdapter', $adapter);
    }


    public function testNewLoginService()
    {
        $service = $this->factory->newLoginService();
        $this->assertInstanceOf('Aura\Auth\Service\LoginService', $service);
    }

    public function testNewLogoutService()
    {
        $service = $this->factory->newLogoutService();
        $this->assertInstanceOf('Aura\Auth\Service\LogoutService', $service);
    }

    public function testNewResumeService()
    {
        $service = $this->factory->newResumeService();
        $this->assertInstanceOf('Aura\Auth\Service\ResumeService', $service);
    }
}
