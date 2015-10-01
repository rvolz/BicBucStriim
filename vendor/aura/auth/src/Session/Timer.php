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

use Aura\Auth\Exception;
use Aura\Auth\Status;

/**
 *
 * Timer
 *
 * @package Aura.Auth
 *
 */
class Timer
{
    /**
     * ini_gc_maxlifetime
     *
     * @var int
     * @access protected
     */
    protected $ini_gc_maxlifetime;

    /**
     * ini_cookie_lifetime
     *
     * @var int
     * @access protected
     */
    protected $ini_cookie_lifetime;

    /**
     *
     * Maximum idle time in seconds; zero is forever.
     *
     * @var int
     *
     */
    protected $idle_ttl = 1440;

    /**
     *
     * Maximum authentication lifetime in seconds; zero is forever.
     *
     * @var int
     *
     */
    protected $expire_ttl = 14400;

    /**
     *
     * Constructor.
     *
     * @param int $ini_gc_maxlifetime
     *
     * @param int $ini_cookie_lifetime
     *
     * @param int $idle_ttl The maximum idle time in seconds.
     *
     * @param int $expire_ttl The maximum authentication time in seconds.
     *
     */
    public function __construct(
        $ini_gc_maxlifetime = 1440,
        $ini_cookie_lifetime = 0,
        $idle_ttl = 1440,
        $expire_ttl = 14400
    ) {
        $this->ini_gc_maxlifetime = $ini_gc_maxlifetime;
        $this->ini_cookie_lifetime = $ini_cookie_lifetime;
        $this->setIdleTtl($idle_ttl);
        $this->setExpireTtl($expire_ttl);
    }

    /**
     *
     * Sets the maximum idle time.
     *
     * @param int $idle_ttl The maximum idle time in seconds.
     *
     * @throws Exception when the session garbage collection max lifetime is
     * less than the idle time.
     *
     * @return null
     *
     */
    public function setIdleTtl($idle_ttl)
    {
        if ($this->ini_gc_maxlifetime < $idle_ttl) {
            throw new Exception('session.gc_maxlifetime less than idle time');
        }
        $this->idle_ttl = $idle_ttl;
    }

    /**
     *
     * Returns the maximum idle time.
     *
     * @return int
     *
     */
    public function getIdleTtl()
    {
        return $this->idle_ttl;
    }

    /**
     *
     * Sets the maximum authentication lifetime.
     *
     * @param int $expire_ttl The maximum authentication lifetime in seconds.
     *
     * @throws Exception when the session cookie lifetime is less than the
     * authentication lifetime.
     *
     * @return null
     *
     */
    public function setExpireTtl($expire_ttl)
    {
        $bad = $this->ini_cookie_lifetime > 0
            && $this->ini_cookie_lifetime < $expire_ttl;
        if ($bad) {
            throw new Exception('session.cookie_lifetime less than expire time');
        }
        $this->expire_ttl = $expire_ttl;
    }

    /**
     *
     * Returns the maximum authentication lifetime.
     *
     * @return int
     *
     */
    public function getExpireTtl()
    {
        return $this->expire_ttl;
    }

    /**
     *
     * Has the authentication time expired?
     *
     * @param int $first_active
     *
     * @return bool
     *
     */
    public function hasExpired($first_active)
    {
        return $this->expire_ttl <= 0
            || ($first_active + $this->getExpireTtl()) < time();
    }

    /**
     *
     * Has the idle time been exceeded?
     *
     * @param int $last_active
     *
     * @return bool
     *
     */
    public function hasIdled($last_active)
    {
        return $this->idle_ttl <= 0
            || ($last_active + $this->getIdleTtl()) < time();
    }

    /**
     *
     * Get Timeout Status
     *
     * @param int $first_active
     *
     * @param int $last_active
     *
     * @return string
     *
     */
    public function getTimeoutStatus($first_active, $last_active)
    {
        if ($this->hasExpired($first_active)) {
            return Status::EXPIRED;
        }

        if ($this->hasIdled($last_active)) {
            return Status::IDLE;
        }
    }
}
