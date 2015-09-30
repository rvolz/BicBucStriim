<?php
namespace Aura\Auth\Session;

class NullSegmentTest extends \PHPUnit_Framework_TestCase
{
    protected $segment;

    public function setUp()
    {
        $this->segment = new NullSegment;
    }

    public function test()
    {
        $this->assertNull($this->segment->get('foo'));
        $this->segment->set('foo', 'bar');
        $this->assertSame('bar', $this->segment->get('foo'));
    }
}
