<?php

namespace Tests\Domain\Calibre;

use App\Domain\Calibre\Utilities;
use PHPUnit\Framework\TestCase;

class UtilitiesTest extends TestCase
{
    public const FIXT = __DIR__ . '/../../fixtures';

    public function testBookPath()
    {
        $this->assertEquals(
            'tests/fixtures/lib2/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub',
            Utilities::bookPath('tests/fixtures/lib2', 'Gotthold Ephraim Lessing/Lob der Faulheit (1)', 'Lob der Faulheit - Gotthold Ephraim Lessing.epub')
        );
    }

    public function testTitleMimeType()
    {
        $this->assertEquals('application/epub+zip', Utilities::titleMimeType('x/y/test.epub'));
        $this->assertEquals('application/x-mobi8-ebook', Utilities::titleMimeType('test.azw3'));
        $this->assertEquals('application/x-mobipocket-ebook', Utilities::titleMimeType('test.mobi'));
        $this->assertEquals('application/x-mobipocket-ebook', Utilities::titleMimeType('test.azw'));
        $this->assertEquals('application/vnd.amazon.ebook', Utilities::titleMimeType('test.azw1'));
        $this->assertEquals('application/vnd.amazon.ebook', Utilities::titleMimeType('test.azw2'));
        $this->assertEquals('text/plain', Utilities::titleMimeType(self::FIXT.'/test.unknown-format'));
        $this->assertEquals('application/xml', Utilities::titleMimeType(self::FIXT.'/atom.rng'));
    }
}
