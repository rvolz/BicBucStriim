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
 * Interface for a session manager.
 *
 * @package Aura.Auth
 *
 */
interface SessionInterface
{
    /**
     *
     * Starts a session.
     *
     */
    public function start();

    /**
     *
     * Resumes a previously-existing session.
     *
     */
    public function resume();

    /**
     *
     * Regenerates the session ID.
     *
     */
    public function regenerateId();
}
