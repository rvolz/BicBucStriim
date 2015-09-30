<?php
namespace Aura\Session;

class SessionFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testNewInstance()
    {
        $session_factory = new SessionFactory;
        $session = $session_factory->newInstance($_COOKIE);
        $this->assertInstanceOf('Aura\Session\Session', $session);
    }
}
