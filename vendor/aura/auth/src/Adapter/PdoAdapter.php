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

use PDO;
use Aura\Auth\Verifier\VerifierInterface;
use Aura\Auth\Exception;

/**
 *
 * Authenticate against an SQL database table via PDO.
 *
 * @package Aura.Auth
 *
 */
class PdoAdapter extends AbstractAdapter
{
    /**
     *
     * A PDO connection object.
     *
     * @var PDO
     *
     */
    protected $pdo;

    /**
     *
     * Columns to be selected.
     *
     * @param array
     *
     */
    protected $cols = array();

    /**
     *
     * Select FROM this table; add JOIN specifications here as needed.
     *
     * @var string
     *
     */
    protected $from;

    /**
     *
     * Added WHERE conditions for the select.
     *
     * @var string
     *
     */
    protected $where;

    /**
     *
     * A verifier for passwords.
     *
     * @var VerifierInterface
     *
     */
    protected $verifier;

    /**
     *
     * Constructor
     *
     * @param \PDO $pdo A PDO connection.
     *
     * @param VerifierInterface $verifier A password verifier.
     *
     * @param array $cols The columns to be selected.
     *
     * @param string $from The table (and joins) to select from.
     *
     * @param string $where The where clause to use.
     *
     */
    public function __construct(
        PDO $pdo,
        VerifierInterface $verifier,
        array $cols,
        $from,
        $where = null
    ) {
        $this->pdo = $pdo;
        $this->verifier = $verifier;
        $this->setCols($cols);
        $this->from = $from;
        $this->where = $where;
    }

    /**
     *
     * Set the $cols property.
     *
     * @param array $cols The columns to select.
     *
     * @return null
     *
     * @throws Exception\UsernameColumnNotSpecified
     *
     * @throws Exception\PasswordColumnNotSpecified
     *
     */
    protected function setCols($cols)
    {
        if (! isset($cols[0]) || trim($cols[0] == '')) {
            throw new Exception\UsernameColumnNotSpecified;
        }
        if (! isset($cols[1]) || trim($cols[1] == '')) {
            throw new Exception\PasswordColumnNotSpecified;
        }
        $this->cols = $cols;
    }

    /**
     *
     * Returns the password verifier.
     *
     * @return VerifierInterface
     *
     */
    public function getVerifier()
    {
        return $this->verifier;
    }

    /**
     *
     * Verifies the username/password credentials.
     *
     * @param array $input An array of credential data, including any data to
     * bind to the query.
     *
     * @return array An array of login data.
     *
     */
    public function login(array $input)
    {
        $this->checkInput($input);
        $data = $this->fetchRow($input);
        $this->verify($input, $data);
        $name = $data['username'];
        unset($data['username']);
        unset($data['password']);
        return array($name, $data);
    }

    /**
     *
     * Fetches a single matching row from the database.
     *
     * @param array $input The user input.
     *
     * @return array The found row.
     *
     * @throws Exception\UsernameNotFound when no row is found.
     *
     * @throws Exception\MultipleMatches where more than one row is found.
     *
     */
    protected function fetchRow($input)
    {
        $stm = $this->buildSelect();
        $rows = $this->fetchRows($stm, $input);

        if (count($rows) < 1) {
            throw new Exception\UsernameNotFound;
        }

        if (count($rows) > 1) {
            throw new Exception\MultipleMatches;
        }

        return $rows[0];
    }

    /**
     *
     * Fetches all matching rows from the database.
     *
     * @param string $stm The SQL statement to execute.
     *
     * @param array $bind Values to bind to the query.
     *
     * @return array
     *
     */
    protected function fetchRows($stm, $bind)
    {
        $sth = $this->pdo->prepare($stm);
        unset($bind['password']);
        $sth->execute($bind);
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     *
     * Builds the SQL statement for finding the user data.
     *
     * @return string
     *
     */
    protected function buildSelect()
    {
        $cols = $this->buildSelectCols();
        $from = $this->buildSelectFrom();
        $where = $this->buildSelectWhere();
        return "SELECT {$cols} FROM {$from} WHERE {$where}";
    }

    /**
     *
     * Builds the SELECT column list.
     *
     * @return string
     *
     */
    protected function buildSelectCols()
    {
        $cols = $this->cols;
        $cols[0] .= ' AS username';
        $cols[1] .= ' AS password';
        return implode(', ', $cols);
    }

    /**
     *
     * Builds the FROM clause.
     *
     * @return string
     *
     */
    protected function buildSelectFrom()
    {
        return $this->from;
    }

    /**
     *
     * Builds the WHERE clause.
     *
     * @return string
     *
     */
    protected function buildSelectWhere()
    {
        $where = $this->cols[0] . " = :username";
        if ($this->where) {
            $where .= " AND ({$this->where})";
        }
        return $where;
    }

    /**
     *
     * Verifies the password.
     *
     * @param array $input The user input array.
     *
     * @param array $data The data from the database.
     *
     * @return bool
     *
     * @throws Exception\PasswordIncorrect
     *
     */
    protected function verify($input, $data)
    {
        $verified = $this->verifier->verify(
            $input['password'],
            $data['password'],
            $data
        );

        if (! $verified) {
            throw new Exception\PasswordIncorrect;
        }

        return true;
    }
}
