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
namespace Aura\Auth\Adapter;

use Aura\Auth\Exception;
use Aura\Auth\Status;
use Aura\Auth\Auth;

/**
 *
 * Authentication adapter
 *
 * @package Aura.Auth
 *
 */
abstract class AbstractAdapter implements AdapterInterface
{
    /**
     *
     * Verifies a set of credentials against a storage backend.
     *
     * @param array $input Credential input.
     *
     * @return array An array of login data on success.
     *
     */
    abstract public function login(array $input);

    /**
     *
     * Handle logout logic against the storage backend.
     *
     * @param Auth $auth The authentication obbject to be logged out.
     *
     * @param string $status The new authentication status after logout.
     *
     * @return null
     *
     * @see Status
     *
     */
    public function logout(Auth $auth, $status = Status::ANON)
    {
        // do nothing
    }

    /**
     *
     * Handle a resumed session against the storage backend.
     *
     * @param Auth $auth The authentication object to be resumed.
     *
     * @return null
     *
     */
    public function resume(Auth $auth)
    {
        // do nothing
    }

    /**
     *
     * Check the credential input for completeness.
     *
     * @param array $input
     *
     * @return bool
     *
     */
    protected function checkInput($input)
    {
        if (empty($input['username'])) {
            throw new Exception\UsernameMissing;
        }

        if (empty($input['password'])) {
            throw new Exception\PasswordMissing;
        }
    }
}
