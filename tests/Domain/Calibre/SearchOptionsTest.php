<?php

namespace Tests\Domain\Calibre;

use App\Domain\Calibre\SearchOptions;
use PHPUnit\Framework\TestCase;

class SearchOptionsTest extends TestCase
{

    public function testToMask()
    {
        $so = new SearchOptions("Test", true, true);
        $this->assertEquals(3, $so->toMask());
        $so = new SearchOptions("Test", true, false);
        $this->assertEquals(1, $so->toMask());
        $so = new SearchOptions("Test", false, true);
        $this->assertEquals(2, $so->toMask());
    }

    public function testFromParams()
    {
        $so = SearchOptions::fromParams("Test", 3);
        $this->assertEquals("Test", $so->getSearchTerm());
        $this->assertTrue($so->isRespectCase());
        $this->assertTrue($so->isUseAsciiTransliteration());
        $so = SearchOptions::fromParams("Test", 1);
        $this->assertTrue($so->isRespectCase());
        $this->assertFalse($so->isUseAsciiTransliteration());
        $so = SearchOptions::fromParams("Test", 22);
        $this->assertFalse($so->isRespectCase());
        $this->assertTrue($so->isUseAsciiTransliteration());
    }
}
