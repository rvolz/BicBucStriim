<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Session;

/**
 *
 * A factory to create CSRF token objects.
 *
 * @package Aura.Session
 *
 */
class CsrfTokenFactory
{
    /**
     *
     * A cryptographically-secure random value generator.
     *
     * @var RandvalInterface
     *
     */
    protected $randval;

    /**
     *
     * Constructor.
     *
     * @param RandvalInterface $randval A cryptographically-secure random
     * value generator.
     *
     */
    public function __construct(RandvalInterface $randval)
    {
        $this->randval = $randval;
    }

    /**
     *
     * Creates a CsrfToken object.
     *
     * @param Session $session The session manager.
     *
     * @return CsrfToken
     *
     */
    public function newInstance(Session $session)
    {
        $segment = $session->getSegment('Aura\Session\CsrfToken');
        return new CsrfToken($segment, $this->randval);
    }
}
