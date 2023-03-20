<?php

namespace Tests\Domain\Calibre;

use App\Domain\BicBucStriim\AppConstants;
use App\Domain\Calibre\Calibre;
use App\Domain\Calibre\CalibreFilter;
use App\Domain\Calibre\SearchOptions;
use PHPUnit\Framework\TestCase;

class CalibreIcuTest extends TestCase
{
    public const CDB4 = __DIR__ . '/../..//fixtures/lib4/metadata.db';

    public $calibre;

    public function setUp(): void
    {
        $this->calibre = new Calibre(self::CDB4);
    }

    public function tearDown(): void
    {
        $this->calibre = null;
    }

    public function testAuthorsSliceSearch()
    {
        $result0 = $this->calibre->authorsSlice(0, 2, new SearchOptions('Асприн'));
        $this->assertCount(1, $result0['entries']);
        $result0 = $this->calibre->authorsSlice(0, 2, new SearchOptions('lôr'));
        $this->assertCount(1, $result0['entries']);
    }

    public function testSeriesSliceSearch()
    {
        $result0 = $this->calibre->seriesSlice(0, 2, new SearchOptions('ü'));
        $this->assertCount(1, $result0['entries']);
    }

    public function testTagsSliceSearch()
    {
        $result0 = $this->calibre->tagsSlice(0, 2, new SearchOptions('I'));
        $this->assertCount(2, $result0['entries']);
        $result0 = $this->calibre->tagsSlice(0, 2, new SearchOptions('Ét'));
        $this->assertCount(1, $result0['entries']);
        $result0 = $this->calibre->tagsSlice(0, 2, new SearchOptions('Ü'));
        $this->assertCount(2, $result0['entries']);
    }

    public function testTitlesSliceSearch()
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
