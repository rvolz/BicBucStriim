<?php
/**
 * Created by IntelliJ IDEA.
 * User: rv
 * Date: 01.10.15
 * Time: 09:39
 */

namespace BicBucStriim;

/**
 *
 * A factory to create session segment objects.
 *
 */
class SegmentFactory extends \Aura\Session\SegmentFactory
{
    /**
     *
     * Creates a session segment object.
     *
     * @param Session $session
     * @param string $name
     *
     * @return Segment
     */
    public function newInstance(Session $session, $name)
    {
        return new Segment($session, $name);
    }
}
