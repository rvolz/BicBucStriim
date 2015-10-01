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

use Aura\Auth\Auth;
use Aura\Auth\Status;

/**
 *
 * Abstract Authentication Storage.
 *
 * @package Aura.Auth
 *
 */
interface AdapterInterface
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
    public function login(array $input);

    /**
     *
     * Handle logout logic against the storage backend.
     *
     * @param Auth $auth The authentication object to be logged out.
     *
     * @param string $status The new authentication status after logout.
     *
     * @return null
     *
     * @see Status
     *
     */
    public function logout(Auth $auth, $status = Status::ANON);

    /**
     *
     * Handle a resumed session against the storage backend.
     *
     * @param Auth $auth The authentication object to be resumed.
     *
     * @return null
     *
     */
    public function resume(Auth $auth);
}
