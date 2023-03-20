<?php

namespace Tests\Domain\Calibre;

use App\Domain\Calibre\Calibre;
use App\Domain\Calibre\CalibreFilter;
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
        $result = $this->calibre->authorsSlice(0, 2, 'Асприн');
        $this->assertCount(1, $result['entries']);
        $this->assertEquals('John Ruskin Асприн', $result['entries'][0]->name);
        $result = $this->calibre->authorsSlice(0, 2, 'Asp', true);
        $this->assertCount(1, $result['entries']);
        $this->assertEquals('John Ruskin Асприн', $result['entries'][0]->name);
        $result = $this->calibre->authorsSlice(0, 2, 'Eras', false);
        $this->assertCount(0, $result['entries']);
        $result = $this->calibre->authorsSlice(0, 2, 'Eras', true);
        $this->assertCount(1, $result['entries']);
        $this->assertEquals('Érasme de Lôrme', $result['entries'][0]->name);
    }

    public function testSeriesSliceSearch()
    {
        $result = $this->calibre->seriesSlice(0, 2, 'Überserie');
        $this->assertCount(1, $result['entries']);
        $this->assertEquals('Überserie', $result['entries'][0]->name);
        $result = $this->calibre->seriesSlice(0, 2, 'Uberserie');
        $this->assertCount(0, $result['entries']);
        $result = $this->calibre->seriesSlice(0, 2, 'Uberserie', true);
        $this->assertCount(1, $result['entries']);
        $this->assertEquals('Überserie', $result['entries'][0]->name);
    }

    public function testTagsSliceSearch()
    {
        $result = $this->calibre->tagsSlice(0, 2, 'Éternité');
        $this->assertCount(1, $result['entries']);
        $this->assertEquals('Éternité', $result['entries'][0]->name);
        $result = $this->calibre->tagsSlice(0, 2, 'Eternite');
        $this->assertCount(0, $result['entries']);
        $result = $this->calibre->tagsSlice(0, 2, 'Eternite', true);
        $this->assertCount(1, $result['entries']);
        $this->assertEquals('Éternité', $result['entries'][0]->name);
    }

    public function testTitlesSliceSearch()
    {
        $result = $this->calibre->titlesSlice('de', 0, 2, new CalibreFilter(), 'Phenix');
        $this->assertCount(0, $result['entries']);
        $result = $this->calibre->titlesSlice('de', 0, 2, new CalibreFilter(), 'Phenix', true);
        $this->assertCount(1, $result['entries']);
        $this->assertEquals('Phénix Òrleans', $result['entries'][0]->title);
        $result = $this->calibre->titlesSlice('de', 0, 2, new CalibreFilter(), 'orleans', true);
        $this->assertCount(1, $result['entries']);
        $this->assertEquals('Phénix Òrleans', $result['entries'][0]->title);
    }
}
