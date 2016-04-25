<?php
/**
 * BicBucStriim
 *
 * Copyright 2012-2016 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 * Test installation check utilities.
 *
 */
set_include_path("tests:vendor");
require_once('simpletest/simpletest/autorun.php');
require 'lib/BicBucStriim/inst_utils.php';


class TestOfInstUtils extends UnitTestCase {

    function setUp() {}

    function tearDown() {}

    ##
    # Test
    #
    function testFindGdVersion() {
        $this->assertEqual("2.1", find_gd_version("gd version 2.1"));
        $this->assertEqual("2.1.0", find_gd_version("gd version 2.1.0"));
        $this->assertEqual("2.1", find_gd_version("gd headers version 2.1"));
        $this->assertEqual("2.1.0", find_gd_version("gd headers version 2.1.0"));
        $this->assertEqual("2.1.0-alpha", find_gd_version("GD headers Version 2.1.0-alpha "));
    }

}
?>
