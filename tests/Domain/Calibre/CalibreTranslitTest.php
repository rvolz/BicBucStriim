<?php

namespace Tests\Domain\Calibre;

use App\Domain\Calibre\Calibre;
use App\Domain\Calibre\CalibreFilter;
use App\Domain\Calibre\SearchOptions;
use PHPUnit\Framework\TestCase;

class CalibreTranslitTest extends TestCase
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

    public function testMkTransliteration()
    {
        $this->assertEquals('Asprin', $this->calibre->mkTransliteration('Асприн'));
        $this->assertEquals('Johann Uberlinger', $this->calibre->mkTransliteration('Johann Überlinger'));
        $this->assertEquals('Erasme de Lorme', $this->calibre->mkTransliteration('Érasme de Lôrme'));
        $this->assertEquals('Hass', $this->calibre->mkTransliteration('Haß'));
    }


    public function testAuthorsSliceSearch()
    {
        $result = $this->calibre->authorsSlice(0, 2, new SearchOptions('Асприн'));
        $this->assertCount(1, $result['entries']);
        $this->assertEquals('John Ruskin Асприн', $result['entries'][0]->name);
        $result = $this->calibre->authorsSlice(0, 2, new SearchOptions('Asp', false, true));
        $this->assertCount(1, $result['entries']);
        $this->assertEquals('John Ruskin Асприн', $result['entries'][0]->name);
        $result = $this->calibre->authorsSlice(0, 2, new SearchOptions('Eras', false, false));
        $this->assertCount(0, $result['entries']);
        $result = $this->calibre->authorsSlice(0, 2, new SearchOptions('Eras', false, true));
        $this->assertCount(1, $result['entries']);
        $this->assertEquals('Érasme de Lôrme', $result['entries'][0]->name);
    }

    public function testSeriesSliceSearch()
    {
        $result = $this->calibre->seriesSlice(0, 2, new SearchOptions('Überserie'));
        $this->assertCount(1, $result['entries']);
        $this->assertEquals('Überserie', $result['entries'][0]->name);
        $result = $this->calibre->seriesSlice(0, 2, new SearchOptions('Uberserie'));
        $this->assertCount(0, $result['entries']);
        $result = $this->calibre->seriesSlice(0, 2, new SearchOptions('Uberserie', false, true));
        $this->assertCount(1, $result['entries']);
        $this->assertEquals('Überserie', $result['entries'][0]->name);
    }

    public function testTagsSliceSearch()
    {
        $result = $this->calibre->tagsSlice(0, 2, new SearchOptions('Éternité'));
        $this->assertCount(1, $result['entries']);
        $this->assertEquals('Éternité', $result['entries'][0]->name);
        $result = $this->calibre->tagsSlice(0, 2, new SearchOptions('Eternite'));
        $this->assertCount(0, $result['entries']);
        $result = $this->calibre->tagsSlice(0, 2, new SearchOptions('Eternite', false, true));
        $this->assertCount(1, $result['entries']);
        $this->assertEquals('Éternité', $result['entries'][0]->name);
    }

    public function testTitlesSliceSearch()
    {
        $result = $this->calibre->titlesSlice('de', 0, 2, new CalibreFilter(), new SearchOptions('Phenix'));
        $this->assertCount(0, $result['entries']);
        $result = $this->calibre->titlesSlice('de', 0, 2, new CalibreFilter(), new SearchOptions('Phenix', false, true));
        $this->assertCount(1, $result['entries']);
        $this->assertEquals('Phénix Òrleans', $result['entries'][0]->title);
        $result = $this->calibre->titlesSlice('de', 0, 2, new CalibreFilter(), new SearchOptions('orleans', false, true));
        $this->assertCount(1, $result['entries']);
        $this->assertEquals('Phénix Òrleans', $result['entries'][0]->title);
    }
}
