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
namespace Aura\Auth;

use Aura\Auth\Adapter;
use Aura\Auth\Service;
use Aura\Auth\Session;
use Aura\Auth\Session\SessionInterface;
use Aura\Auth\Session\SegmentInterface;
use Aura\Auth\Verifier;
use Aura\Auth\Adapter\AdapterInterface;
use PDO;

/**
 *
 * Factory for Auth package objects.
 *
 * @package Aura.Auth
 *
 */

class AuthFactory
{
    /**
     *
     * A session manager.
     *
     * @var SessionInterface
     *
     */
    protected $session;

    /**
     *
     * A session segment.
     *
     * @var SegmentInterface
     *
     */
    protected $segment;

    /**
     *
     * Constructor.
     *
     * @param array $cookie A copy of $_COOKIES.
     *
     * @param SessionInterface $session A session manager.
     *
     * @param SegmentInterface $segment A session segment.
     *
     */
    public function __construct(
        array $cookie,
        SessionInterface $session = null,
        SegmentInterface $segment = null
    ) {
        $this->session = $session;
        if (! $this->session) {
            $this->session = new Session\Session($cookie);
        }

        $this->segment = $segment;
        if (! $this->segment) {
            $this->segment = new Session\Segment;
        }
    }

    /**
     *
     * Returns a new authentication tracker.
     *
     * @return Auth
     *
     */
    public function newInstance()
    {
        return new Auth($this->segment);
    }

    /**
     *
     * Returns a new login service instance.
     *
     * @param AdapterInterface $adapter The adapter to use with the service.
     *
     * @return Service\LoginService
     *
     */
    public function newLoginService(AdapterInterface $adapter = null)
    {
        return new Service\LoginService(
            $this->fixAdapter($adapter),
            $this->session
        );
    }

    /**
     *
     * Returns a new logout service instance.
     *
     * @param AdapterInterface $adapter The adapter to use with the service.
     *
     * @return Service\LogoutService
     *
     */
    public function newLogoutService(AdapterInterface $adapter = null)
    {
        return new Service\LogoutService(
            $this->fixAdapter($adapter),
            $this->session
        );
    }

    /**
     *
     * Returns a new "resume session" service.
     *
     * @param AdapterInterface $adapter The adapter to use with the service, and
     * with the underlying logout service.
     *
     * @param int $idle_ttl The session idle time in seconds.
     *
     * @param int $expire_ttl The session expire time in seconds.
     *
     * @return Service\ResumeService
     *
     */
    public function newResumeService(
        AdapterInterface $adapter = null,
        $idle_ttl = 1440,
        $expire_ttl = 14400
    ) {

        $adapter = $this->fixAdapter($adapter);

        $timer = new Session\Timer(
            ini_get('session.gc_maxlifetime'),
            ini_get('session.cookie_lifetime'),
            $idle_ttl,
            $expire_ttl
        );

        $logout_service = new Service\LogoutService(
            $adapter,
            $this->session
        );

        return new Service\ResumeService(
            $adapter,
            $this->session,
            $timer,
            $logout_service
        );
    }

    /**
     *
     * Make sure we have an Adapter instance, even if only a NullAdapter.
     *
     * @param Adapterinterface $adapter Check to make sure this is an Adapter
     * instance.
     *
     * @return AdapterInterface
     *
     */
    protected function fixAdapter(AdapterInterface $adapter = null)
    {
        if ($adapter === null) {
            $adapter = new Adapter\NullAdapter;
        }
        return $adapter;
    }

    /**
     *
     * Returns a new PDO adapter.
     *
     * @param PDO $pdo A PDO connection.
     *
     * @param mixed $verifier_spec Specification to pick a verifier: if an
     * object, assume a VerifierInterface; otherwise, assume a PASSWORD_*
     * constant for a PasswordVerifier.
     *
     * @param array $cols Select these columns.
     *
     * @param string $from Select from this table (and joins).
     *
     * @param string $where WHERE conditions for the select.
     *
     * @return Adapter\PdoAdapter
     *
     */
    public function newPdoAdapter(
        PDO $pdo,
        $verifier_spec,
        array $cols,
        $from,
        $where = null
    ) {
        if (is_object($verifier_spec)) {
            $verifier = $verifier_spec;
        } else {
            $verifier = new Verifier\PasswordVerifier($verifier_spec);
        }

        return new Adapter\PdoAdapter(
            $pdo,
            $verifier,
            $cols,
            $from,
            $where
        );
    }

    /**
     *
     * Returns a new HtpasswdAdapter.
     *
     * @param string $file Path to the htpasswd file.
     *
     * @return Adapter\HtpasswdAdapter
     *
     */
    public function newHtpasswdAdapter($file)
    {
        $verifier = new Verifier\HtpasswdVerifier;
        return new Adapter\HtpasswdAdapter(
            $file,
            $verifier
        );
    }

    /**
     *
     * Returns a new ImapAdapter.
     *
     * @param string $mailbox An imap_open() mailbox string.
     *
     * @param int $options Options for the imap_open() call.
     *
     * @param int $retries Try to connect this many times.
     *
     * @param array $params Set these params after opening the connection.
     *
     * @return Adapter\ImapAdapter
     *
     */
    public function newImapAdapter(
        $mailbox,
        $options = 0,
        $retries = 1,
        array $params = null
    ) {
        return new Adapter\ImapAdapter(
            new Phpfunc,
            $mailbox,
            $options,
            $retries,
            $params
        );
    }

    /**
     *
     * Returns a new LdapAdapter.
     *
     * @param string $server An LDAP server string.
     *
     * @param string $dnformat A distinguished name format string for looking up
     * the username.
     *
     * @param array $options Use these connection options.
     *
     * @return Adapter\LdapAdapter
     *
     */
    public function newLdapAdapter(
        $server,
        $dnformat,
        array $options = array()
    ) {
        return new Adapter\LdapAdapter(
            new Phpfunc,
            $server,
            $dnformat,
            $options
        );
    }
}
