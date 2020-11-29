<?php
/**
 * Test our workaround to search for items with non-ascii names
 */
set_include_path("tests:vendor");
require_once('simpletest/simpletest/autorun.php');
require_once('lib/BicBucStriim/calibre.php');
require_once('lib/BicBucStriim/calibre_filter.php');

class TestOfCalibreIcu extends UnitTestCase
{

    const CDB4 = './tests/fixtures/lib4/metadata.db';

    var $calibre;

    function setUp()
    {
        $this->calibre = new Calibre(self::CDB4);
    }

    function tearDown()
    {
        $this->calibre = null;
    }

    function testAuthorsSliceSearch()
    {
        $result0 = $this->calibre->authorsSlice(0, 2, 'Асприн');
        $this->assertEqual(1, count($result0['entries']));
        $result0 = $this->calibre->authorsSlice(0, 2, 'lôr');
        $this->assertEqual(1, count($result0['entries']));
    }

    function testSeriesSliceSearch()
    {
        $result0 = $this->calibre->seriesSlice(0, 2, 'ü');
        $this->assertEqual(1, count($result0['entries']));
    }

    function testTagsSliceSearch()
    {
        $result0 = $this->calibre->tagsSlice(0, 2, 'I');
        $this->assertEqual(2, count($result0['entries']));
        $result0 = $this->calibre->tagsSlice(0, 2, 'Ét');
        $this->assertEqual(1, count($result0['entries']));
        $result0 = $this->calibre->tagsSlice(0, 2, 'Ü');
        $this->assertEqual(2, count($result0['entries']));
    }

    function testTitlesSliceSearch()
    {
        $result0 = $this->calibre->titlesSlice('de', 0, 2, new CalibreFilter(), 'ü');
        $this->assertEqual(1, count($result0['entries']));
        $result0 = $this->calibre->titlesSlice('de', 0, 2, new CalibreFilter(), 'ä');
        $this->assertEqual(1, count($result0['entries']));
        $result0 = $this->calibre->titlesSlice('de', 0, 2, new CalibreFilter(), 'ß');
        $this->assertEqual(1, count($result0['entries']));
        $result0 = $this->calibre->titlesSlice('fr', 0, 2, new CalibreFilter(), 'é');
        $this->assertEqual(1, count($result0['entries']));
        $result0 = $this->calibre->titlesSlice('fr', 0, 2, new CalibreFilter(), 'ò');
        $this->assertEqual(1, count($result0['entries']));
    }

}