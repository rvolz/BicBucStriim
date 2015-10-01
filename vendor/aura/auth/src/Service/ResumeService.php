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

use Aura\Auth\Auth;
use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\Session\SessionInterface;
use Aura\Auth\Session\Timer;

/**
 *
 * Resume handler.
 *
 * @package Aura.Auth
 *
 */
class ResumeService
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
     * A session timer.
     *
     * @var Timer
     *
     */
    protected $timer;

    /**
     *
     * The logout handler to use if the session has timed out.
     *
     * @var LogoutService
     *
     */
    protected $logout_service;

    /**
     *
     * Constructor.
     *
     * @param AdapterInterface $adapter A credential storage adapter.
     *
     * @param SessionInterface $session A session manager.
     *
     * @param Timer $timer A session timer.
     *
     * @param LogoutService $logout_service The logout handler to use if the
     * session has timed out.
     *
     */
    public function __construct(
        AdapterInterface $adapter,
        SessionInterface $session,
        Timer $timer,
        LogoutService $logout_service
    ) {
        $this->adapter = $adapter;
        $this->session = $session;
        $this->timer = $timer;
        $this->logout_service = $logout_service;
    }

    /**
     *
     * Resumes any previous session, logging out the user as idled or
     * expired if needed.
     *
     * @param Auth $auth An authentication tracker.
     *
     * @return bool Whether or not a session still exists.
     *
     */
    public function resume(Auth $auth)
    {
        $this->session->resume();
        if (! $this->timedOut($auth)) {
            $auth->setLastActive(time());
            $this->adapter->resume($auth);
        }
    }

    /**
     *
     * Sets the user timeout status, and logs out if expired.
     *
     * @param Auth $auth An authentication tracker.
     *
     * @return bool
     *
     */
    protected function timedOut(Auth $auth)
    {
        if ($auth->isAnon()) {
            return false;
        }

        $timeout_status = $this->timer->getTimeoutStatus(
            $auth->getFirstActive(),
            $auth->getLastActive()
        );

        if ($timeout_status) {
            $auth->setStatus($timeout_status);
            $this->logout_service->logout($auth, $timeout_status);
            return true;
        }

        return false;
    }
}
