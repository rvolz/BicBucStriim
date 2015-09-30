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
 * Intercept calls to PHP functions.
 *
 * @package Aura.Session
 *
 */
class Phpfunc
{
    /**
     *
     * Magic call to intercept any function pass to it.
     *
     * @param string $func The function to call.
     *
     * @param array $args Arguments passed to the function.
     *
     * @return mixed The result of the function call.
     *
     */
    public function __call($func, $args)
    {
        return call_user_func_array($func, $args);
    }
}
