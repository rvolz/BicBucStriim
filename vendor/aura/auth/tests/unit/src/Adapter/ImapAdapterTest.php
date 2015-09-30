<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\Phpfunc;

class ImapAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $phpfunc;

    protected $adapter;

    protected function setUp()
    {
        $this->phpfunc = $this->getMock(
            'Aura\Auth\Phpfunc',
            array(
                'imap_open',
                'imap_close',
            )
        );

        $this->adapter = new ImapAdapter(
            $this->phpfunc,
            '{mailbox.example.com:143/imap/secure}'
        );
    }

    public function testInstance()
    {
        $this->assertInstanceOf(
            'Aura\Auth\Adapter\ImapAdapter',
            $this->adapter
        );
    }

    public function testLogin()
    {
        $this->phpfunc->expects($this->once())
            ->method('imap_open')
            ->with(
                '{mailbox.example.com:143/imap/secure}',
                'someusername',
                'secretpassword',
                0,
                1,
                null
            )
            ->will($this->returnValue(true));

        $actual = $this->adapter->login(array(
            'username' => 'someusername',
            'password' => 'secretpassword'
        ));

        $expect = array('someusername', array());

        $this->assertSame($expect, $actual);
    }

    public function testLogin_connectionFailed()
    {
        $this->phpfunc->expects($this->once())
            ->method('imap_open')
            ->with('{mailbox.example.com:143/imap/secure}')
            ->will($this->returnValue(false));

        $this->setExpectedException('Aura\Auth\Exception\ConnectionFailed');
        $this->adapter->login(array(
            'username' => 'someusername',
            'password' => 'secretpassword'
        ));
    }
}
