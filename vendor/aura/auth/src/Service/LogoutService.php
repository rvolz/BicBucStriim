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
namespace Aura\Auth\Service;

use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\Session\SessionInterface;
use Aura\Auth\Status;
use Aura\Auth\Auth;

/**
 *
 * Logout handler.
 *
 * @package Aura.Auth
 *
 */
class LogoutService
{
    /**
     *
     * A credential storage adapter.
     *
     * @var AdapterInterface
     *
     */
    protected $adapter;

    /**
     *
     * A session manager.
     *
     * @var SessionInterface
     *
     */
    protected $session;

    /**
     *
     * Constructor.
     *
     * @param AdapterInterface $adapter A credential storage adapter.
     *
     * @param SessionInterface $session A session manager.
     *
     */
    public function __construct(
        AdapterInterface $adapter,
        SessionInterface $session
    ) {
        $this->adapter = $adapter;
        $this->session = $session;
    }

    /**
     *
     * Log the user out via the adapter.
     *
     * @param Auth $auth An authentication tracker.
     *
     * @param string $status The status after logout.
     *
     * @return null
     *
     */
    public function logout(Auth $auth, $status = Status::ANON)
    {
        $this->adapter->logout($auth, $status);
        $this->forceLogout($auth, $status);
    }

    /**
     *
     * Forces a successful logout.
     *
     * @param Auth $auth An authentication tracker.
     *
     * @param string $status The status after logout.
     *
     * @return string The new authentication status.
     *
     */
    public function forceLogout(Auth $auth, $status = Status::ANON)
    {
        $this->session->regenerateId();

        $auth->set(
            $status,
            null,
            null,
            null,
            array()
        );

        return $status;
    }
}
