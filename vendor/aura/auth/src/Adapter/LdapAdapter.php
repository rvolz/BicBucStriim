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

use Aura\Auth\Exception;
use Aura\Auth\Phpfunc;

/**
 *
 * Authenticate against an LDAP server.
 *
 * @package Aura.Auth
 *
 */
class LdapAdapter extends AbstractAdapter
{
    /**
     *
     * An LDAP server connection string.
     *
     * @var string
     *
     */
    protected $server;

    /**
     *
     * An sprintf() format string for the LDAP query.
     *
     * @var string
     *
     */
    protected $dnformat = null;

    /**
     *
     * Set these options after the LDAP connection.
     *
     * @var array
     *
     */
    protected $options = array();

    /**
     *
     * An object to intercept PHP calls.
     *
     * @var Phpfunc
     *
     */
    protected $phpfunc;

    /**
     *
     * Constructor.
     *
     * @param Phpfunc $phpfunc An object to intercept PHP calls.
     *
     * @param string $server An LDAP server connection string.
     *
     * @param string $dnformat An sprintf() format string for the LDAP query.
     *
     * @param array $options Set these options after the LDAP connection.
     *
     */
    public function __construct(
        Phpfunc $phpfunc,
        $server,
        $dnformat,
        array $options = array()
    ) {
        $this->phpfunc = $phpfunc;
        $this->server = $server;
        $this->dnformat = $dnformat;
        $this->options = $options;
    }

    /**
     *
     * Verifies a set of credentials.
     *
     * @param array $input The 'username' and 'password' to verify.
     *
     * @return mixed An array of verified user information, or boolean false
     * if verification failed.
     *
     */
    public function login(array $input)
    {
        $this->checkInput($input);
        $username = $input['username'];
        $password = $input['password'];

        $conn = $this->connect();
        $this->bind($conn, $username, $password);
        return array($username, array());
    }

    /**
     *
     * Connects to the LDAP server and sets options.
     *
     * @return resource The LDAP connection.
     *
     * @throws Exception\ConnectionFailed when the connection fails.
     *
     */
    protected function connect()
    {
        $conn = $this->phpfunc->ldap_connect($this->server);
        if (! $conn) {
            throw new Exception\ConnectionFailed($this->server);
        }

        foreach ($this->options as $opt => $val) {
            $this->phpfunc->ldap_set_option($conn, $opt, $val);
        }

        return $conn;
    }

    /**
     *
     * Binds to the LDAP server with username and password.
     *
     * @param resource $conn The LDAP connection.
     *
     * @param string $username The input username.
     *
     * @param string $password The input password.
     *
     * @throws Exception\BindFailed when the username/password fails.
     *
     */
    protected function bind($conn, $username, $password)
    {
        $username = $this->escape($username);
        $bind_rdn = sprintf($this->dnformat, $username);

        $bound = $this->phpfunc->ldap_bind($conn, $bind_rdn, $password);
        if (! $bound) {
            $error = $this->phpfunc->ldap_errno($conn)
                   . ': '
                   . $this->phpfunc->ldap_error($conn);
            $this->phpfunc->ldap_close($conn);
            throw new Exception\BindFailed($error);
        }

        $this->phpfunc->ldap_unbind($conn);
        $this->phpfunc->ldap_close($conn);
    }

    /**
     *
     * Escapes input values for LDAP string.
     *
     * Per <http://projects.webappsec.org/w/page/13246947/LDAP%20Injection>
     * and <https://www.owasp.org/index.php/Preventing_LDAP_Injection_in_Java>.
     *
     * @param string $str The string to be escaped.
     *
     * @return string The escaped string.
     *
     */
    protected function escape($str)
    {
        return strtr($str, array(
            '\\' => '\\\\',
            '&'  => '\\&',
            '!'  => '\\!',
            '|'  => '\\|',
            '='  => '\\=',
            '<'  => '\\<',
            '>'  => '\\>',
            ','  => '\\,',
            '+'  => '\\+',
            '-'  => '\\-',
            '"'  => '\\"',
            "'"  => "\\'",
            ';'  => '\\;',
        ));
    }
}
