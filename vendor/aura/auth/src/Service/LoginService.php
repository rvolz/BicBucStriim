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
 * Login handler
 *
 * @package Aura.Auth
 *
 */
class LoginService
{
    /**
     *
     * Adapter of Adapterinterface
     *
     * @var mixed
     *
     */
    protected $adapter;

    /**
     *
     * session
     *
     * @var SessionInterface
     *
     */
    protected $session;

    /**
     *
     * Constructor.
     *
     * @param AdapterInterface $adapter A credential-storage adapter.
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
     * Logs the user in via the credential adapter.
     *
     * @param Auth $auth The authentication tracking object.
     *
     * @param array $input The credential input.
     *
     * @return null
     *
     */
    public function login(Auth $auth, array $input)
    {
        list($name, $data) = $this->adapter->login($input);
        $this->forceLogin($auth, $name, $data);
    }

    /**
     *
     * Forces a successful login.
     *
     * @param Auth $auth The authentication tracking object.
     *
     * @param string $name The authenticated user name.
     *
     * @param string $data Additional arbitrary user data.
     *
     * @param string $status The new authentication status.
     *
     * @return string|false The authentication status on success, or boolean
     * false on failure.
     *
     */
    public function forceLogin(
        Auth $auth,
        $name,
        array $data = array(),
        $status = Status::VALID
    ) {
        $started = $this->session->resume() || $this->session->start();
        if (! $started) {
            return false;
        }

        $this->session->regenerateId();
        $auth->set(
            $status,
            time(),
            time(),
            $name,
            $data
        );

        return $status;
    }
}
