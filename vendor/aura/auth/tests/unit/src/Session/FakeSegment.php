<?php
namespace Aura\Auth\Session;

class FakeSegment implements SegmentInterface
{
    public function get($key, $alt = null)
    {
        if (isset($this->$key)) {
            return $this->$key;
        }

        return $alt;
    }

    public function set($key, $val)
    {
        $this->$key = $val;
    }
}
