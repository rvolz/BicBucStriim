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

/**
 *
 * NullAdapter
 *
 * @package Aura.Auth
 *
 */
class NullAdapter extends AbstractAdapter
{
    /**
     *
     * login
     *
     * @param array $input
     *
     * @return array
     *
     */
    public function login(array $input)
    {
        return array(null, null);
    }
}
