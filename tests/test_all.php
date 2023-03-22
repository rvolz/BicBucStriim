<?php

set_include_path("tests:vendor");
#require_once('lib/simpletest/autorun.php');
require 'autoload.php';
require 'simpletest/simpletest/autorun.php';
class TestsAll extends TestSuite
{
    public function __construct(string $label = 'All Tests')
    {
        parent::__construct($label);
        $this->addFile('test_bicbucstriim.php');
        $this->addFile('test_calibre.php');
        $this->addFile('test_calibre_filter.php');
        $this->addFile('test_calibre_icu.php');
        $this->addFile('test_custom_columns.php');
        $this->addFile('test_l10n.php');
        $this->addFile('test_utilities.php');
        // TODO reenable OPDS tests
        //$this->addFile('test_opds_generator.php');
        $this->addFile('test_inst_utils.php');
    }
}
