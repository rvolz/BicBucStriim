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
namespace Aura\Auth;

use Aura\Auth\Session\SegmentInterface;
use Aura\Auth\Session\SessionInterface;
use Aura\Auth\Session\Timer;

/**
 *
 * The current user (authenticated or otherwise).
 *
 * @package Aura.Auth
 *
 */
class Auth
{
    /**
     *
     * Session data.
     *
     * @var SegmentInterface
     *
     */
    protected $segment;

    /**
     *
     * Constructor.
     *
     * @param SegmentInterface $segment A session data store.
     *
     */
    public function __construct(SegmentInterface $segment)
    {
        $this->segment = $segment;
    }

    /**
     *
     * Sets the authentication values.
     *
     * @param string $status The authentication status.
     *
     * @param int $first_active First active at this Unix time.
     *
     * @param int $last_active Last active at this Unix time.
     *
     * @param string $username The username.
     *
     * @param array $userdata Arbitrary user data.
     *
     * @return null
     *
     * @see Status for constants and their values.
     *
     */
    public function set(
        $status,
        $first_active,
        $last_active,
        $username,
        array $userdata
    ) {
        $this->setStatus($status);
        $this->setFirstActive($first_active);
        $this->setLastActive($last_active);
        $this->setUserName($username);
        $this->setUserData($userdata);
    }

    /**
     *
     * Is the user authenticated?
     *
     * @return bool
     *
     */
    public function isValid()
    {
        return $this->getStatus() == Status::VALID;
    }

    /**
     *
     * Is the user anonymous?
     *
     * @return bool
     *
     */
    public function isAnon()
    {
        return $this->getStatus() == Status::ANON;
    }

    /**
     *
     * Has the user been idle for too long?
     *
     * @return bool
     *
     */
    public function isIdle()
    {
        return $this->getStatus() == Status::IDLE;
    }

    /**
     *
     * Has the authentication time expired?
     *
     * @return bool
     *
     */
    public function isExpired()
    {
        return $this->getStatus() == Status::EXPIRED;
    }

    /**
     *
     * Sets the current authentication status.
     *
     * @param string $status The authentication status.
     *
     * @return null
     *
     */
    public function setStatus($status)
    {
        $this->segment->set('status', $status);
    }

    /**
     *
     * Gets the current authentication status.
     *
     * @return string
     *
     */
    public function getStatus()
    {
        return $this->segment->get('status', Status::ANON);
    }

    /**
     *
     * Sets the initial authentication time.
     *
     * @param int $first_active The initial authentication Unix time.
     *
     * @return null
     *
     */
    public function setFirstActive($first_active)
    {
        $this->segment->set('first_active', $first_active);
    }

    /**
     *
     * Gets the initial authentication time.
     *
     * @return int
     *
     */
    public function getFirstActive()
    {
        return $this->segment->get('first_active');
    }

    /**
     *
     * Sets the last active time.
     *
     * @param int $last_active The last active Unix time.
     *
     * @return null
     *
     */
    public function setLastActive($last_active)
    {
        $this->segment->set('last_active', $last_active);
    }

    /**
     *
     * Gets the last active time.
     *
     * @return int
     *
     */
    public function getLastActive()
    {
        return $this->segment->get('last_active');
    }

    /**
     *
     * Sets the current user name.
     *
     * @param string $username The username.
     *
     * @return null
     *
     */
    public function setUserName($username)
    {
        $this->segment->set('username', $username);
    }

    /**
     *
     * Gets the current user name.
     *
     * @return string
     *
     */
    public function getUserName()
    {
        return $this->segment->get('username');
    }

    /**
     *
     * Sets the current user data.
     *
     * @param array $data The user data.
     *
     * @return null
     *
     */
    public function setUserData(array $userdata)
    {
        $this->segment->set('userdata', $userdata);
    }

    /**
     *
     * Gets the current user data.
     *
     * @return array
     *
     */
    public function getUserData()
    {
        return $this->segment->get('userdata', array());
    }
}
