<?php

set_include_path("tests:vendor");
require_once('simpletest/simpletest/autorun.php');
require_once('lib/BicBucStriim/langs.php');
require_once('lib/BicBucStriim/l10n.php');

class TestOfL10N extends UnitTestCase
{
    public function setUp()
    {
    }

    public function tearDown()
    {
    }

    ##
    # Test array functionality
    #
    public function testArrayGet()
    {
        global $langde;

        $l10n = new L10n('de');
        $this->assertEqual($langde['admin'], $l10n->message('admin'));
        $this->assertEqual($langde['admin'], $l10n['admin']);
        $this->assertEqual('Undefined message!', $l10n['bla bla']);
    }
}
