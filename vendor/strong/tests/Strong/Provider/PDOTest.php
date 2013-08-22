<?php

use Strong\Strong;

class Strong_Provider_PDOTest extends Strong_Provider_ProviderTesting {

    public function getObj() {
        return new Strong(array(
            'name' => 'pdoTest',
            'provider' => 'PDO',
            'pdo' => new PDOMock(),
        ));
    }

    public function testCreateInstanceInvalid() {
        $this->setExpectedException('\InvalidArgumentException', 'You must add valid pdo connection object');
        $strong = new Strong(array(
            'name' => 'pdoTestInvalid',
            'provider' => 'PDO',
        ));
    }

    public function testCreateInstancePDOisInvalidObject() {
        $this->setExpectedException('\InvalidArgumentException', 'You must add valid pdo connection object');
        $strong = new Strong(array(
            'name' => 'pdoTestInvalid2',
            'provider' => 'PDO',
            'pdo' => null,
        ));
    }

}