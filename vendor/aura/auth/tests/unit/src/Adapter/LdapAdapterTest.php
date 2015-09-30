<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\Phpfunc;

class LdapAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $adapter;

    protected $phpfunc;

    protected function setUp()
    {
        $this->phpfunc = $this->getMock(
            'Aura\Auth\Phpfunc',
            array(
                'ldap_connect',
                'ldap_bind',
                'ldap_unbind',
                'ldap_set_option',
                'ldap_close',
                'ldap_errno',
                'ldap_error'
            )
        );

        $this->adapter = new LdapAdapter(
            $this->phpfunc,
            'ldaps://ldap.example.com:636',
            'ou=Foo,dc=Bar,cn=users,uid=%s',
            array('LDAP_OPTION_KEY', 'LDAP_OPTION_VALUE')
        );
    }

    public function testInstance()
    {
        $this->assertInstanceOf(
            'Aura\Auth\Adapter\LdapAdapter',
            $this->adapter
        );
    }

    public function testLogin()
    {
        $this->phpfunc->expects($this->once())
            ->method('ldap_connect')
            ->with('ldaps://ldap.example.com:636')
            ->will($this->returnValue(true));

        $this->phpfunc->expects($this->any())
            ->method('ldap_set_option')
            ->will($this->returnValue(true));

        $this->phpfunc->expects($this->once())
            ->method('ldap_bind')
            ->with(
                true,
                'ou=Foo,dc=Bar,cn=users,uid=someusername',
                'secretpassword'
            )
            ->will($this->returnValue(true));

        $this->phpfunc->expects($this->once())
            ->method('ldap_unbind')
            ->will($this->returnValue(true));

        $this->phpfunc->expects($this->once())
            ->method('ldap_close')
            ->will($this->returnValue(true));

        $actual = $this->adapter->login(array(
            'username' => 'someusername',
            'password' => 'secretpassword'
        ));

        $this->assertEquals(
            array('someusername', array()),
            $actual
        );
    }

    public function testLogin_connectionFailed()
    {
        $input = array(
            'username' => 'someusername',
            'password' => 'secretpassword'
        );
        $this->phpfunc->expects($this->once())
            ->method('ldap_connect')
            ->with('ldaps://ldap.example.com:636')
            ->will($this->returnValue(false));

        $this->setExpectedException('Aura\Auth\Exception\ConnectionFailed');
        $this->adapter->login($input);
    }

    public function testLogin_bindFailed()
    {
        $this->phpfunc->expects($this->once())
            ->method('ldap_connect')
            ->with('ldaps://ldap.example.com:636')
            ->will($this->returnValue(true));

        $this->phpfunc->expects($this->any())
            ->method('ldap_set_option')
            ->will($this->returnValue(true));

        $this->phpfunc->expects($this->once())
            ->method('ldap_bind')
            ->will($this->returnValue(false));

        $this->phpfunc->expects($this->once())
            ->method('ldap_errno')
            ->will($this->returnValue(1));

        $this->phpfunc->expects($this->once())
            ->method('ldap_error')
            ->will($this->returnValue('Operations Error'));

        $this->phpfunc->expects($this->once())
            ->method('ldap_close')
            ->will($this->returnValue(true));

        $this->setExpectedException('Aura\Auth\Exception\BindFailed');
        $this->adapter->login(array(
            'username' => 'someusername',
            'password' => 'secretpassword'
        ));
    }
}
