<?php

set_include_path("tests:vendor");
require_once('simpletest/simpletest/autorun.php');
require_once('lib/BicBucStriim/bicbucstriim.php');

class TestOfCustomColumns extends UnitTestCase
{
    public const CDB1 = './tests/fixtures/metadata_empty.db';
    public const CDB2 = './tests/fixtures/lib2/metadata.db';
    public const CDB3 = './tests/fixtures/lib3/metadata.db';

    public const DB2 = './tests/fixtures/data2.db';

    public const DATA = './tests/data';
    public const DATADB = './tests/data/data.db';

    public $bbs;
    public $calibre;

    public function setUp()
    {
        $this->calibre = new Calibre(self::CDB2);
    }

    public function tearDown()
    {
        $this->calibre = null;
    }

    # Lots of ccs -- one with multiple values
    public function testCustomColumns()
    {
        $ccs = $this->calibre->customColumns(7);
        #print_r($ccs);
        $this->assertEqual(9, sizeof($ccs));
        $this->assertEqual('col2a, col2b', $ccs['Col2']['value']);
    }

    # Ignore series ccs for now
    public function testCustomColumnsIgnoreSeries()
    {
        $ccs = $this->calibre->customColumns(5);
        #print_r($ccs);
        $this->assertEqual(0, sizeof($ccs));
    }

    # Only one cc
    public function testCustomColumnsJustOneCC()
    {
        $ccs = $this->calibre->customColumns(1);
        $this->assertEqual(1, sizeof($ccs));
    }
}
