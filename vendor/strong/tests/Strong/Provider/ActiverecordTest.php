<?php

use Strong\Strong;

class Strong_Provider_ActiverecordTest extends Strong_Provider_ProviderTesting {

    public function getObj() {
        return new Strong(array(
            'name' => 'activerecordTest',
            'provider' => 'Activerecord',
        ));
    }

    public function testCheckCounter() {
        $this->testLoginValid();
        $this->assertEquals(2, User::$checkLogins);
    }

}