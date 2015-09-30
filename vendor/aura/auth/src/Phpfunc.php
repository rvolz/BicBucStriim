<?php
/**
 *
 * This file is part of the Aura project for PHP.
 *
 * @package Aura.Auth
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth;

/**
 *
 * Proxy for the ease of testing PHP functions.
 *
 * http://mikenaberezny.com/2007/08/01/wrapping-php-functions-for-testability/
 *
 * @package Aura.Auth
 *
 */

class Phpfunc
{
    /**
     *
     * Magic call for PHP functions.
     *
     * @param string $method The PHP function to call.
     *
     * @param array $params Params to pass to the function.
     *
     * @return mixed
     *
     */
    public function __call($method, $params)
    {
        return call_user_func_array($method, $params);
    }
}
