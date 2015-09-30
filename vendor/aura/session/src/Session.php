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
 * A central control point for new session segments, PHP session management
 * values, and CSRF token checking.
 *
 * @package Aura.Session
 *
 */
class Session
{
    /**
     *
     * Session key for the "next" flash values.
     *
     * @const string
     *
     */
    const FLASH_NEXT = 'Aura\Session\Flash\Next';

    /**
     *
     * Session key for the "current" flash values.
     *
     * @const string
     *
     */
    const FLASH_NOW = 'Aura\Session\Flash\Now';

    /**
     *
     * A session segment factory.
     *
     * @var SegmentFactory
     *
     */
    protected $segment_factory;

    /**
     *
     * The CSRF token for this session.
     *
     * @var CsrfToken
     *
     */
    protected $csrf_token;

    /**
     *
     * A CSRF token factory, for lazy-creating the CSRF token.
     *
     * @var CsrfTokenFactory
     *
     */
    protected $csrf_token_factory;

    /**
     *
     * Incoming cookies from the client, typically a copy of the $_COOKIE
     * superglobal.
     *
     * @var array
     *
     */
    protected $cookies;

    /**
     *
     * Session cookie parameters.
     *
     * @var array
     *
     */
    protected $cookie_params = array();

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
     * A callable to invoke when deleting the session cookie. The callable
     * should have the signature ...
     *
     *      function ($cookie_name, $cookie_params)
     *
     * ... and return null.
     *
     * @var callable|null
     *
     * @see setDeleteCookie()
     *
     */
    protected $delete_cookie;

    /**
     *
     * Have the flash values been moved forward?
     *
     * @var bool
     *
     */
    protected $flash_moved = false;

    /**
     *
     * Constructor
     *
     * @param SegmentFactory $segment_factory A session segment factory.
     *
     * @param CsrfTokenFactory $csrf_token_factory A CSRF token factory.
     *
     * @param Phpfunc $phpfunc An object to intercept PHP function calls;
     * this makes testing easier.
     *
     * @param array $cookies Optional: An array of cookies from the client, typically a
     * copy of $_COOKIE. Empty array by default.
     *
     * @param callable|null $delete_cookie Optional: An alternative callable
     * to invoke when deleting the session cookie. Defaults to `null`.
     *
     */
    public function __construct(
        SegmentFactory $segment_factory,
        CsrfTokenFactory $csrf_token_factory,
        Phpfunc $phpfunc,
        array $cookies = array(),
        $delete_cookie = null
    )
    {
        $this->segment_factory = $segment_factory;
        $this->csrf_token_factory = $csrf_token_factory;
        $this->phpfunc = $phpfunc;
        $this->cookies = $cookies;

        $this->setDeleteCookie($delete_cookie);

        $this->cookie_params = $this->phpfunc->session_get_cookie_params();
    }

    /**
     *
     * Sets the delete-cookie callable.
     *
     * If parameter is `null`, the session cookie will be deleted using the
     * traditional way, i.e. using an expiration date in the past.
     *
     * @param callable|null $delete_cookie The callable to invoke when deleting the
     * session cookie.
     *
     */
    public function setDeleteCookie($delete_cookie)
    {
        $this->delete_cookie = $delete_cookie;
        if (!$this->delete_cookie) {
            $phpfunc = $this->phpfunc;
            $this->delete_cookie = function (
                $name,
                $params
            ) use ($phpfunc) {
                $phpfunc->setcookie(
                    $name,
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain']
                );
            };
        }
    }

    /**
     *
     * Gets a new session segment instance by name. Segments with the same
     * name will be different objects but will reference the same $_SESSION
     * values, so it is possible to have two or more objects that share state.
     * For good or bad, this a function of how $_SESSION works.
     *
     * @param string $name The name of the session segment, typically a
     * fully-qualified class name.
     *
     * @return Segment New Segment instance.
     *
     */
    public function getSegment($name)
    {
        return $this->segment_factory->newInstance($this, $name);
    }

    /**
     *
     * Is a session available to be resumed?
     *
     * @return bool
     *
     */
    public function isResumable()
    {
        $name = $this->getName();
        return isset($this->cookies[$name]);
    }

    /**
     *
     * Is the session already started?
     *
     * @return bool
     *
     */
    public function isStarted()
    {
        if ($this->phpfunc->function_exists('session_status')) {
            $started = $this->phpfunc->session_status() === PHP_SESSION_ACTIVE;
        } else {
            $started = $this->sessionStatus();
        }

        // if the session was started externally, move the flash values forward
        if ($started && !$this->flash_moved) {
            $this->moveFlash();
        }

        // done
        return $started;
    }

    /**
     *
     * Returns the session status.
     *
     * Nota bene:
     *
     * PHP 5.3 implementation of session_status() for only active/none.
     * Relies on the fact that ini setting 'session.use_trans_sid' cannot be
     * changed when a session is active.
     *
     * PHP ini_set() raises a warning when we attempt to change this setting
     * and session is active. Note that the attempted change is to the
     * pre-existing value, so nothing will actually change on success.
     *
     */
    protected function sessionStatus()
    {
        $setting = 'session.use_trans_sid';
        $current = $this->phpfunc->ini_get($setting);
        $level = $this->phpfunc->error_reporting(0);
        $result = $this->phpfunc->ini_set($setting, $current);
        $this->phpfunc->error_reporting($level);
        return $result !== $current;
    }

    /**
     *
     * Starts a new or existing session.
     *
     * @return bool
     *
     */
    public function start()
    {
        $result = $this->phpfunc->session_start();
        if ($result) {
            $this->moveFlash();
        }
        return $result;
    }

    /**
     *
     * Moves the "next" flash values to the "now" values, thereby clearing the
     * "next" values.
     *
     * @return null
     *
     */
    protected function moveFlash()
    {
        if (!isset($_SESSION[Session::FLASH_NEXT])) {
            $_SESSION[Session::FLASH_NEXT] = array();
        }
        $_SESSION[Session::FLASH_NOW] = $_SESSION[Session::FLASH_NEXT];
        $_SESSION[Session::FLASH_NEXT] = array();
        $this->flash_moved = true;
    }

    /**
     *
     * Resumes a session, but does not start a new one if there is no
     * existing one.
     *
     * @return bool
     *
     */
    public function resume()
    {
        if ($this->isStarted()) {
            return true;
        }

        if ($this->isResumable()) {
            return $this->start();
        }

        return false;
    }

    /**
     *
     * Clears all session variables across all segments.
     *
     * @return null
     *
     */
    public function clear()
    {
        return $this->phpfunc->session_unset();
    }

    /**
     *
     * Writes session data from all segments and ends the session.
     *
     * @return null
     *
     */
    public function commit()
    {
        return $this->phpfunc->session_write_close();
    }

    /**
     *
     * Destroys the session entirely.
     *
     * @return bool
     *
     * @see http://php.net/manual/en/function.session-destroy.php
     *
     */
    public function destroy()
    {
        if (!$this->isStarted()) {
            $this->start();
        }

        $name = $this->getName();
        $params = $this->getCookieParams();
        $this->clear();

        $destroyed = $this->phpfunc->session_destroy();
        if ($destroyed) {
            call_user_func($this->delete_cookie, $name, $params);
        }

        return $destroyed;
    }

    /**
     *
     * Returns the CSRF token, creating it if needed (and thereby starting a
     * session).
     *
     * @return CsrfToken
     *
     */
    public function getCsrfToken()
    {
        if (!$this->csrf_token) {
            $this->csrf_token = $this->csrf_token_factory->newInstance($this);
        }

        return $this->csrf_token;
    }

    // =======================================================================
    //
    // support and admin methods
    //

    /**
     *
     * Sets the session cache expire time.
     *
     * @param int $expire The expiration time in seconds.
     *
     * @return int
     *
     * @see session_cache_expire()
     *
     */
    public function setCacheExpire($expire)
    {
        return $this->phpfunc->session_cache_expire($expire);
    }

    /**
     *
     * Gets the session cache expire time.
     *
     * @return int The cache expiration time in seconds.
     *
     * @see session_cache_expire()
     *
     */
    public function getCacheExpire()
    {
        return $this->phpfunc->session_cache_expire();
    }

    /**
     *
     * Sets the session cache limiter value.
     *
     * @param string $limiter The limiter value.
     *
     * @return string
     *
     * @see session_cache_limiter()
     *
     */
    public function setCacheLimiter($limiter)
    {
        return $this->phpfunc->session_cache_limiter($limiter);
    }

    /**
     *
     * Gets the session cache limiter value.
     *
     * @return string The limiter value.
     *
     * @see session_cache_limiter()
     *
     */
    public function getCacheLimiter()
    {
        return $this->phpfunc->session_cache_limiter();
    }

    /**
     *
     * Sets the session cookie params.  Param array keys are:
     *
     * - `lifetime` : Lifetime of the session cookie, defined in seconds.
     *
     * - `path` : Path on the domain where the cookie will work.
     *   Use a single slash ('/') for all paths on the domain.
     *
     * - `domain` : Cookie domain, for example 'www.php.net'.
     *   To make cookies visible on all subdomains then the domain must be
     *   prefixed with a dot like '.php.net'.
     *
     * - `secure` : If TRUE cookie will only be sent over secure connections.
     *
     * - `httponly` : If set to TRUE then PHP will attempt to send the httponly
     *   flag when setting the session cookie.
     *
     * @param array $params The array of session cookie param keys and values.
     *
     * @return null
     *
     * @see session_set_cookie_params()
     *
     */
    public function setCookieParams(array $params)
    {
        $this->cookie_params = array_merge($this->cookie_params, $params);
        $this->phpfunc->session_set_cookie_params(
            $this->cookie_params['lifetime'],
            $this->cookie_params['path'],
            $this->cookie_params['domain'],
            $this->cookie_params['secure'],
            $this->cookie_params['httponly']
        );
    }

    /**
     *
     * Gets the session cookie params.
     *
     * @return array
     *
     */
    public function getCookieParams()
    {
        return $this->cookie_params;
    }

    /**
     *
     * Gets the current session id.
     *
     * @return string
     *
     */
    public function getId()
    {
        return $this->phpfunc->session_id();
    }

    /**
     *
     * Regenerates and replaces the current session id; also regenerates the
     * CSRF token value if one exists.
     *
     * @return bool True if regeneration worked, false if not.
     *
     */
    public function regenerateId()
    {
        $result = $this->phpfunc->session_regenerate_id(true);
        if ($result && $this->csrf_token) {
            $this->csrf_token->regenerateValue();
        }
        return $result;
    }

    /**
     *
     * Sets the current session name.
     *
     * @param string $name The session name to use.
     *
     * @return string
     *
     * @see session_name()
     *
     */
    public function setName($name)
    {
        return $this->phpfunc->session_name($name);
    }

    /**
     *
     * Returns the current session name.
     *
     * @return string
     *
     */
    public function getName()
    {
        return $this->phpfunc->session_name();
    }

    /**
     *
     * Sets the session save path.
     *
     * @param string $path The new save path.
     *
     * @return string
     *
     * @see session_save_path()
     *
     */
    public function setSavePath($path)
    {
        return $this->phpfunc->session_save_path($path);
    }

    /**
     *
     * Gets the session save path.
     *
     * @return string
     *
     * @see session_save_path()
     *
     */
    public function getSavePath()
    {
        return $this->phpfunc->session_save_path();
    }
}
