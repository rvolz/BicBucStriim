<?php

class PDOMock extends PDO {

    public function __construct () {

    }

    public function prepare($statement, $options = NULL) {
        return new StmtMock();
    }
}