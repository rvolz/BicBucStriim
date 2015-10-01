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
 * Authenticate against an IMAP, POP3, or NNTP server.
 *
 * @package Aura.Auth
 *
 */
class ImapAdapter extends AbstractAdapter
{
    /**
     *
     * An imap_open() mailbox string; e.g., "{mail.example.com:143/imap/secure}"
     * or "{mail.example.com:110/pop3/secure}".
     *
     * @var string
     *
     */
    protected $mailbox;

    /**
     *
     * Options for the imap_open() call.
     *
     * @var int
     *
     */
    protected $options = 0;

    /**
     *
     * Try to connect this many times.
     *
     * @var int
     *
     */
    protected $retries = 1;

    /**
     *
     * Params for the imap_open() call.
     *
     * @var array|null
     *
     */
    protected $params;

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
     * @param string $mailbox The imap_open() mailbox string.
     *
     * @param int $options Options for the imap_open() call.
     *
     * @param int $retries Try connecting this many times.
     *
     * @param array $params Params for the imap_open() call.
     *
     */
    public function __construct(
        Phpfunc $phpfunc,
        $mailbox,
        $options = 0,
        $retries = 1,
        array $params = null
    ) {
        $this->phpfunc = $phpfunc;
        $this->mailbox = $mailbox;
        $this->options = $options;
        $this->retries = $retries;
        $this->params = $params;
    }

    /**
     *
     * Verifies a set of credentials.
     *
     * @param array $input An array of credential data, including any data to
     * bind to the query.
     *
     * @return array An array of login data.
     *
     * @throws Exception\ConnectionFailed when the IMAP connection fails.
     *
     */
    public function login(array $input)
    {
        $this->checkInput($input);
        $username = $input['username'];
        $password = $input['password'];

        $conn = $this->phpfunc->imap_open(
            $this->mailbox,
            $username,
            $password,
            $this->options,
            $this->retries,
            $this->params
        );

        if (! $conn) {
            throw new Exception\ConnectionFailed($this->mailbox);
        }

        $this->phpfunc->imap_close($conn);
        return array($username, array());
    }
}
