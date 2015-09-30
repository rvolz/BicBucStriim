<?php
namespace Aura\Auth;

class PhpfuncTest extends \PHPUnit_Framework_TestCase
{
    protected $phpfunc;

    protected function setUp()
    {
        $this->phpfunc = new Phpfunc;
    }

    public function testInstance()
    {
        $this->assertEquals(
            str_replace('Hello', 'Hi', 'Hello Aura'),
            $this->phpfunc->str_replace('Hello', 'Hi', 'Hello Aura')
        );
    }
}
