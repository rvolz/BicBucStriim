<?php

use Strong\Strong;

class Strong_Provider_HashtableTest extends Strong_Provider_ProviderTesting {

    public function getObj() {
        return new Strong(array(
            'name' => 'hashtableTest',
            'provider' => 'Hashtable',
            'users' => array('admin' => 'pass')
        ));
    }

    public function testCreateInstanceInvalid() {
        $this->setExpectedException('\InvalidArgumentException', 'No declare users');
        $strong = new Strong(array(
            'name' => 'hashtableTestInvalid',
            'provider' => 'Hashtable',
        ));
    }

    public function testCreateInstanceUsersNotArray() {
        $this->setExpectedException('\InvalidArgumentException', 'No declare users');
        $strong = new Strong(array(
            'name' => 'hashtableTestInvalid2',
            'provider' => 'Hashtable',
            'users' => 'test',
        ));
    }

}