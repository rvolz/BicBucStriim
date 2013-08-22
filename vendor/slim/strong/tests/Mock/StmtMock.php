<?php

class StmtMock extends \PDOStatement {

    private $value;

    public function bindParam($paramno, &$param, $type = NULL, $maxlen = NULL, $driverdata = NULL) {
        $this->value = $param;
    }

    public function fetch($how = NULL, $orientation = NULL, $offset = NULL) {
        if ($this->value === null)
            return null;
        return (object) array(
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin',
            'password' => password_hash('pass', PASSWORD_BCRYPT),
        );
    }

}