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
 * Session manager.
 *
 * @package Aura.Auth
 *
 */
class NullSession implements SessionInterface
{
    /**
     *
     * Start Session
     *
     * @return bool
     *
     */
    public function start()
    {
        return true;
    }

    /**
     *
     * Resume previous session
     *
     * @return bool
     *
     */
    public function resume()
    {
        return false;
    }

    /**
     *
     * Re generate session id
     *
     * @return mixed
     *
     */
    public function regenerateId()
    {
        return true;
    }
}
