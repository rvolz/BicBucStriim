<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @package Aura.Auth
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth\Session;

/**
 *
 * A $_SESSION segment; it attaches to $_SESSION lazily (i.e., only after a
 * session becomes available.)
 *
 * @package Aura.Auth
 *
 */
class Segment implements SegmentInterface
{
    /**
     *
     * The name of the $_SESSION segment, typically a class name.
     *
     * @var string
     *
     */
    protected $name;

    /**
     *
     * Constructor.
     *
     * @param bool $name The name of the $_SESSION segment.
     *
     */
    public function __construct($name = 'Aura\Auth\Auth')
    {
        $this->name = $name;
    }

    /**
     *
     * Gets a value from the segment.
     *
     * @param mixed $key A key for the segment value.
     *
     * @param mixed $alt Return this value if the segment key does not exist.
     *
     * @return mixed
     *
     */
    public function get($key, $alt = null)
    {
        if (isset($_SESSION[$this->name][$key])) {
            return $_SESSION[$this->name][$key];
        }

        return $alt;
    }

    /**
     *
     * Sets a value in the segment.
     *
     * @param mixed $key The key in the segment.
     *
     * @param mixed $val The value to set.
     *
     */
    public function set($key, $val)
    {
        if (! isset($_SESSION)) {
            return;
        }

        $_SESSION[$this->name][$key] = $val;
    }
}
