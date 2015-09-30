<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Session;

use Aura\Session\Exception;

/**
 *
 * Generates cryptographically-secure random values.
 *
 * @package Aura.Session
 *
 */
class Randval implements RandvalInterface
{
    /**
     *
     * An object to intercept PHP function calls; this makes testing easier.
     *
     * @var Phpfunc
     *
     */
    protected $phpfunc;

    /**
     *
     * Constructor.
     *
     * @param Phpfunc $phpfunc An object to intercept PHP function calls;
     * this makes testing easier.
     *
     */
    public function __construct(Phpfunc $phpfunc)
    {
        $this->phpfunc = $phpfunc;
    }

    /**
     *
     * Returns a cryptographically secure random value.
     *
     * @return string
     *
     * @throws Exception if neither openssl nor mcrypt is available.
     *
     */
    public function generate()
    {
        $bytes = 32;

        if ($this->phpfunc->extension_loaded('openssl')) {
            return $this->phpfunc->openssl_random_pseudo_bytes($bytes);
        }

        if ($this->phpfunc->extension_loaded('mcrypt')) {
            return $this->phpfunc->mcrypt_create_iv($bytes, MCRYPT_DEV_URANDOM);
        }

        $message = "Cannot generate cryptographically secure random values. "
            . "Please install extension 'openssl' or 'mcrypt', or use "
            . "another cryptographically secure implementation.";

        throw new Exception($message);
    }
}
