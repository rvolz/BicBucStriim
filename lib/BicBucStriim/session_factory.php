<?php
/**
 * Created by IntelliJ IDEA.
 * User: rv
 * Date: 01.10.15
 * Time: 09:37
 */

namespace BicBucStriim;

use Aura\Session\Randval;
use Aura\Session\CsrfTokenFactory;
use Aura\Session\Phpfunc;

/**
 *
 * A factory to create a Session manager.
 *
 */
class SessionFactory extends \Aura\Session\SessionFactory
{
    /**
     *
     * Creates a new Session manager.
     *
     * @param array $cookies An array of cookie values, typically $_COOKIE.
     *
     * @param callable|null $delete_cookie Optional: An alternative callable
     * to invoke when deleting the session cookie. Defaults to `null`.
     *
     * @return Session New Session manager instance
     */
    public function newInstance(array $cookies, $delete_cookie = null)
    {
        $phpfunc = new Phpfunc;
        return new Session(
            new SegmentFactory,
            new CsrfTokenFactory(new Randval($phpfunc)),
            $phpfunc,
            $cookies,
            $delete_cookie
        );
    }
}