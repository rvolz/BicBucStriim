<?php

namespace Tests\Domain\Calibre;

use App\Domain\Calibre\Calibre;
use App\Domain\Calibre\CalibreFilter;
use App\Domain\Calibre\SearchOptions;
use PHPUnit\Framework\TestCase;

class CalibreIcuTest extends TestCase
{
    const CDB4 = __DIR__ . '/../..//fixtures/lib4/metadata.db';

    var $calibre;

    function setUp(): void
    {
        $this->calibre = new Calibre(self::CDB4);
    }

    function tearDown(): void
    {
        $this->calibre = null;
    }

    function testAuthorsSliceSearch()
    {
        $result0 = $this->calibre->authorsSlice(0, 2, new SearchOptions('Асприн'));
        $this->assertCount(1, $result0['entries']);
        $result0 = $this->calibre->authorsSlice(0, 2, new SearchOptions('lôr'));
        $this->assertCount(1, $result0['entries']);
    }

    function testSeriesSliceSearch()
    {
        $result0 = $this->calibre->seriesSlice(0, 2, new SearchOptions('ü'));
        $this->assertCount(1, $result0['entries']);
    }

    function testTagsSliceSearch()
    {
        $result0 = $this->calibre->tagsSlice(0, 2, new SearchOptions('I'));
        $this->assertCount(2, $result0['entries']);
        $result0 = $this->calibre->tagsSlice(0, 2, new SearchOptions('Ét'));
        $this->assertCount(1, $result0['entries']);
        $result0 = $this->calibre->tagsSlice(0, 2, new SearchOptions('Ü'));
        $this->assertCount(2, $result0['entries']);
    }

    function testTitlesSliceSearch()
    {
        $result0 = $this->calibre->titlesSlice('de', 0, 2, new CalibreFilter(), new SearchOptions('ü'));
        $this->assertCount(1, $result0['entries']);
        $result0 = $this->calibre->titlesSlice('de', 0, 2, new CalibreFilter(), new SearchOptions('ä'));
        $this->assertCount(1, $result0['entries']);
        $result0 = $this->calibre->titlesSlice('de', 0, 2, new CalibreFilter(), new SearchOptions('ß'));
        $this->assertCount(1, $result0['entries']);
        $result0 = $this->calibre->titlesSlice('fr', 0, 2, new CalibreFilter(), new SearchOptions('é'));
        $this->assertCount(1, $result0['entries']);
        $result0 = $this->calibre->titlesSlice('fr', 0, 2, new CalibreFilter(), new SearchOptions('ò'));
        $this->assertCount(1, $result0['entries']);
    }
}
