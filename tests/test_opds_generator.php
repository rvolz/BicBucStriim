<?php
set_include_path("tests");
require_once('lib/simpletest/autorun.php');
require_once('lib/BicBucStriim/bicbucstriim.php');
require_once('lib/BicBucStriim/opds_generator.php');


class TestOfOpdsGenerator extends UnitTestCase {
	const OPDS_RNG = './tests/fixtures/opds_catalog_1_1.rng';
	const DATA = './tests/data';
	const DB2 = './tests/fixtures/data2.db';
	const CDB2 = './tests/fixtures/lib2/metadata.db';
	const DATADB = './tests/data/data.db';

	var $bbs;
	var $gen;

	function setUp() {
		if (file_exists(self::DATA))
			system("rm -rf ".self::DATA);	
    mkdir(self::DATA);
    chmod(self::DATA,0777);
    copy(self::DB2, self::DATADB);
    $this->bbs = new BicBucStriim(self::DATADB);
    $this->bbs->openCalibreDb(self::CDB2);
		$this->gen = new OpdsGenerator('/bbs', '0.9.0', $this->bbs->calibre_dir, date(DATE_ATOM, strtotime('2012-01-01T11:59:59')));    
	}

	function tearDown() {
		$this->bbs = NULL;
		system("rm -rf ".self::DATA);
	}

	# Validation helper
	function opdsValidate($feed) {
		$res = system('jing '.self::OPDS_RNG.' '.$feed);
		if ($res != '') {
			echo 'OPDS validation error: '.$res;
			return false;
		} else
			return true;
	}

	function testRootCatalogValidation() {
		$feed = self::DATA.'/feed.xml';
		$xml = $this->gen->rootCatalog($feed);
		$this->assertTrue(file_exists($feed));		
		$this->assertTrue($this->opdsValidate($feed));
	}

	function testPartialAcquisitionEntry() {
		$expected = '<entry>
 <id>urn:bicbucstriim:/bbs/titles/2</id>
 <title>Trutz Simplex</title>
 <dc:issued>2012</dc:issued>
 <updated>2012-01-01T11:59:59+01:00</updated>
 <author>
  <name>Grimmelshausen, Hans Jakob Christoffel von</name>
 </author>
 <link href="/bbs/titles/2/thumbnail/" type="image/png" rel="http://opds-spec.org/image/thumbnail"/>
 <link href="/bbs/titles/2/file/Trutz+Simplex+-+Hans+Jakob+Christoffel+von+Grimmelshausen.epub" type="application/epub+zip" rel="http://opds-spec.org/acquisition"/>
 <link href="/bbs/titles/2/file/Trutz+Simplex+-+Hans+Jakob+Christoffel+von+Grimmelshausen.mobi" type="application/x-mobipocket-ebook" rel="http://opds-spec.org/acquisition"/>
</entry>
';
		$just_book = $this->bbs->title(2);
		#print_r($just_book);
		$book = $this->bbs->titleDetailsOpds($just_book);
		$this->gen->openStream(NULL);
		$this->gen->partialAcquisitionEntry($book, false);
		$result = $this->gen->closeStream();
		#print_r($result);
		$this->assertEqual($expected, $result);
	}

function testPartialAcquisitionEntryWithProtection() {
		$expected = '<entry>
 <id>urn:bicbucstriim:/bbs/titles/2</id>
 <title>Trutz Simplex</title>
 <dc:issued>2012</dc:issued>
 <updated>2012-01-01T11:59:59+01:00</updated>
 <author>
  <name>Grimmelshausen, Hans Jakob Christoffel von</name>
 </author>
 <link href="/bbs/titles/2/thumbnail/" type="image/png" rel="http://opds-spec.org/image/thumbnail"/>
 <link href="/bbs/titles/2/showaccess/" type="text/html" rel="http://opds-spec.org/acquisition">
  <opds:indirectAcquisition type="application/epub+zip"/>
 </link>
 <link href="/bbs/titles/2/showaccess/" type="text/html" rel="http://opds-spec.org/acquisition">
  <opds:indirectAcquisition type="application/x-mobipocket-ebook"/>
 </link>
</entry>
';
		$just_book = $this->bbs->title(2);
		$book = $this->bbs->titleDetailsOpds($just_book);
		$this->gen->openStream(NULL);
		$this->gen->partialAcquisitionEntry($book, true);
		$result = $this->gen->closeStream();
		#print_r($result);
		$this->assertEqual($expected, $result);
	}	
}
?>
