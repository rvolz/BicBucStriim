<?php

set_include_path("tests:vendor");
require_once('simpletest/simpletest/autorun.php');
require_once('lib/BicBucStriim/utilities.php');

class TestOfUtilities extends UnitTestCase
{
    public const FIXT = './tests/fixtures';

    public function testConstructUrlInfoSimple()
    {
        $gen = new UrlInfo('host.org', null);
        $this->assertTrue($gen->is_valid());
        $this->assertEqual('host.org', $gen->host);
        $this->assertEqual('http', $gen->protocol);

        $gen = new UrlInfo('host.org', 'https');
        $this->assertTrue($gen->is_valid());
        $this->assertEqual('host.org', $gen->host);
        $this->assertEqual('https', $gen->protocol);
    }

    public function testConstructUrlInfoForwarded()
    {
        $input1 = "for=192.0.2.60;proto=http;by=203.0.113.43";
        $input2 = "for=192.0.2.60;proto=https;by=203.0.113.43";
        $input3 = "for=192.0.2.60;by=203.0.113.43;proto=https";

        $gen = new UrlInfo($input1);
        $this->assertTrue($gen->is_valid());
        $this->assertEqual('203.0.113.43', $gen->host);
        $this->assertEqual('http', $gen->protocol);

        $gen = new UrlInfo($input2);
        $this->assertTrue($gen->is_valid());
        $this->assertEqual('203.0.113.43', $gen->host);
        $this->assertEqual('https', $gen->protocol);

        $gen = new UrlInfo($input3);
        $this->assertTrue($gen->is_valid());
        $this->assertEqual('https', $gen->protocol);
    }

    public function testBookPath()
    {
        $this->assertEqual(
            'tests/fixtures/lib2/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub',
            Utilities::bookPath('tests/fixtures/lib2', 'Gotthold Ephraim Lessing/Lob der Faulheit (1)', 'Lob der Faulheit - Gotthold Ephraim Lessing.epub')
        );
    }

    public function testTitleMimeType()
    {
        $this->assertEqual('application/epub+zip', Utilities::titleMimeType('x/y/test.epub'));
        $this->assertEqual('application/x-mobi8-ebook', Utilities::titleMimeType('test.azw3'));
        $this->assertEqual('application/x-mobipocket-ebook', Utilities::titleMimeType('test.mobi'));
        $this->assertEqual('application/x-mobipocket-ebook', Utilities::titleMimeType('test.azw'));
        $this->assertEqual('application/vnd.amazon.ebook', Utilities::titleMimeType('test.azw1'));
        $this->assertEqual('application/vnd.amazon.ebook', Utilities::titleMimeType('test.azw2'));
        $this->assertEqual('text/plain', Utilities::titleMimeType(self::FIXT.'/test.unknown-format'));
        $this->assertEqual('application/xml', Utilities::titleMimeType(self::FIXT.'/atom.rng'));
    }
}
