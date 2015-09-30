<?php
namespace Aura\Session\_Config;

use Aura\Di\_Config\AbstractContainerTest;

class ContainerTest extends AbstractContainerTest
{
    protected function getConfigClasses()
    {
        return array(
            'Aura\Session\_Config\Common',
        );
    }

    protected function getAutoResolve()
    {
        return false;
    }

    public function provideGet()
    {
        return array(
            array('aura/session:session', 'Aura\Session\Session')
        );
    }

    public function provideNewInstance()
    {
        return array(
            array('Aura\Session\CsrfTokenFactory'),
            array('Aura\Session\Session'),
            array('Aura\Session\Randval'),
            array('Aura\Session\Segment', array(
                'name' => 'fake-name',
            ))
        );
    }
}
