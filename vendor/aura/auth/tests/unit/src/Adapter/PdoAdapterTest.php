<?php
namespace Aura\Auth\Adapter;

use PDO;
use Aura\Auth\Verifier\PasswordVerifier;

class PdoAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $adapter;

    protected $pdo;

    protected function setUp()
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->buildTable();
        $this->setAdapter();
    }

    protected function setAdapter($where = null)
    {
        $this->adapter = new PdoAdapter(
            $this->pdo,
            new PasswordVerifier('md5'),
            array('username', 'password', 'active'),
            'accounts',
            $where
        );
    }

    protected function buildTable()
    {
        $stm = "CREATE TABLE accounts (
            username VARCHAR(255),
            password VARCHAR(255),
            active VARCHAR(255)
        )";

        $this->pdo->query($stm);

        $rows = array(
            array(
                'username' => 'boshag',
                'password' => hash('md5', '123456'),
                'active'    => 'y',
            ),
            array(
                'username' => 'repeat',
                'password' => hash('md5', '234567'),
                'active'    => 'y',
            ),
            array(
                'username' => 'repeat',
                'password' => hash('md5', '234567'),
                'active'    => 'n',
            ),
        );

        $stm = "INSERT INTO accounts (username, password, active)
                VALUES (:username, :password, :active)";

        $sth = $this->pdo->prepare($stm);

        foreach ($rows as $row) {
            $sth->execute($row);
        }
    }

    public function test_usernameColumnNotSpecified()
    {
        $this->setExpectedException('Aura\Auth\Exception\UsernameColumnNotSpecified');
        $this->adapter = new PdoAdapter(
            $this->pdo,
            new PasswordVerifier('md5'),
            array(),
            'accounts'
        );
    }

    public function test_passwordColumnNotSpecified()
    {
        $this->setExpectedException('Aura\Auth\Exception\PasswordColumnNotSpecified');
        $this->adapter = new PdoAdapter(
            $this->pdo,
            new PasswordVerifier('md5'),
            array('username'),
            'accounts'
        );
    }

    public function testLogin()
    {
        list($name, $data) = $this->adapter->login(array(
            'username' => 'boshag',
            'password' => '123456',
        ));

        $this->assertSame('boshag', $name);
        $this->assertSame(array('active' => 'y'), $data);
    }

    public function testLogin_usernameMissing()
    {
        $this->setExpectedException('Aura\Auth\Exception\UsernameMissing');
        $this->adapter->login(array());
    }

    public function testLogin_passwordMissing()
    {
        $this->setExpectedException('Aura\Auth\Exception\PasswordMissing');
        $this->adapter->login(array(
            'username' => 'boshag',
        ));
    }

    public function testLogin_usernameNotFound()
    {
        $this->setExpectedException('Aura\Auth\Exception\UsernameNotFound');
        $this->adapter->login(array(
            'username' => 'missing',
            'password' => '------',
        ));
    }

    public function testLogin_passwordIncorrect()
    {
        $this->setExpectedException('Aura\Auth\Exception\PasswordIncorrect');
        $this->adapter->login(array(
            'username' => 'boshag',
            'password' => '------',
        ));
    }

    public function testLogin_multipleMatches()
    {
        $this->setExpectedException('Aura\Auth\Exception\MultipleMatches');
        $this->adapter->login(array(
            'username' => 'repeat',
            'password' => '234567',
        ));
    }

    public function testLogin_where()
    {
        $this->setAdapter("active = :active");
        list($name, $data) = $this->adapter->login(array(
            'username' => 'repeat',
            'password' => '234567',
            'active' => 'y',
        ));
        $this->assertSame('repeat', $name);
        $this->assertSame(array('active' => 'y'), $data);
    }
}
