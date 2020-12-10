<?php

namespace Tests\Infrastructure;

use App\Infrastructure\InstUtils;
use PHPUnit\Framework\TestCase;

class InstUtilsTest extends TestCase
{

    function testFindGdVersion() {
        $this->assertEquals("2.1", InstUtils::find_gd_version("gd version 2.1"));
        $this->assertEquals("2.1.0", InstUtils::find_gd_version("gd version 2.1.0"));
        $this->assertEquals("2.1", InstUtils::find_gd_version("gd headers version 2.1"));
        $this->assertEquals("2.1.0", InstUtils::find_gd_version("gd headers version 2.1.0"));
        $this->assertEquals("2.1.0-alpha", InstUtils::find_gd_version("GD headers Version 2.1.0-alpha "));
    }
}
