<?php
/**
 * OPDS Generator test suite.
 *
 * Needs the follwoing external tools installed:
 * - jing (http://code.google.com/p/jing-trang/)
 * - opds_validator (https://github.com/zetaben/opds-validator)
 */
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

	# Validation helper: validate relaxng 
	function opdsValidateSchema($feed) {
		$res = system('jing '.self::OPDS_RNG.' '.$feed);
		if ($res != '') {
			echo 'RelaxNG validation error: '.$res;
			return false;
		} else
			return true;
	}

	# Validation helper: validate opds
	function opdsValidate($feed, $version) {
		$cmd = 'cd ~/lib/java/opds-validator;java -jar OPDSvalidator.jar -v'.$version.' '.realpath($feed);
		$res = system($cmd);
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
		$this->assertTrue($this->opdsValidateSchema($feed));
		$this->assertTrue($this->opdsValidate($feed,'1.0'));
		$this->assertTrue($this->opdsValidate($feed,'1.1'));
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

	function testNewestCatalogValidation() {
		$feed = self::DATA.'/feed.xml';
		$just_books = $this->bbs->last30Books();
		$books = $this->bbs->titleDetailsFilteredOpds($just_books);		
		$xml = $this->gen->newestCatalog($feed,$books,false);
		$this->assertTrue(file_exists($feed));		
		$this->assertTrue($this->opdsValidateSchema($feed));
		$this->assertTrue($this->opdsValidate($feed,'1.0'));
		$this->assertTrue($this->opdsValidate($feed,'1.1'));
	}

	function testTitlesCatalogValidation() {
		$feed = self::DATA.'/feed.xml';
		$tl = $this->bbs->titlesSlice(0,2);
		$books = $this->bbs->titleDetailsFilteredOpds($tl['entries']);		
		$xml = $this->gen->titlesCatalog($feed,$books,false, 
			$tl['page'],$tl['page']+1,$tl['pages']-1);
		$this->assertTrue(file_exists($feed));		
		$this->assertTrue($this->opdsValidateSchema($feed));
		$this->assertTrue($this->opdsValidate($feed,'1.0'));
		$this->assertTrue($this->opdsValidate($feed,'1.1'));
	}

	function testAuthorsInitialCatalogValidation() {
		$feed = self::DATA.'/feed.xml';
		$tl = $this->bbs->authorsInitials();
		$xml = $this->gen->authorsRootCatalog($feed,$tl);
		$this->assertTrue(file_exists($feed));		
		$this->assertTrue($this->opdsValidateSchema($feed));
		$this->assertTrue($this->opdsValidate($feed,'1.0'));
		$this->assertTrue($this->opdsValidate($feed,'1.1'));
	}

	function testAuthorsNamesForInitialCatalogValidation() {
		$feed = self::DATA.'/feed.xml';
		$tl = $this->bbs->authorsNamesForInitial('R');
		$xml = $this->gen->authorsNamesForInitialCatalog($feed,$tl, 'R');
		$this->assertTrue(file_exists($feed));		
		$this->assertTrue($this->opdsValidateSchema($feed));
		$this->assertTrue($this->opdsValidate($feed,'1.0'));
		$this->assertTrue($this->opdsValidate($feed,'1.1'));
	}

	function testAuthorsBooksForAuthorCatalogValidation() {
		$feed = self::DATA.'/feed.xml';
		$adetails = $this->bbs->authorDetails(5);
		$books = $this->bbs->titleDetailsFilteredOpds($adetails['books']);
		$xml = $this->gen->booksForAuthorCatalog($feed,$books, 'E', $adetails, false);
		$this->assertTrue(file_exists($feed));		
		$this->assertTrue($this->opdsValidateSchema($feed));
		$this->assertTrue($this->opdsValidate($feed,'1.0'));
		$this->assertTrue($this->opdsValidate($feed,'1.1'));
	}

	function testTagsInitialCatalogValidation() {
		$feed = self::DATA.'/feed.xml';
		$tl = $this->bbs->tagsInitials();
		$xml = $this->gen->tagsRootCatalog($feed,$tl);
		$this->assertTrue(file_exists($feed));		
		$this->assertTrue($this->opdsValidateSchema($feed));
		$this->assertTrue($this->opdsValidate($feed,'1.0'));
		$this->assertTrue($this->opdsValidate($feed,'1.1'));
	}

	function testTagsNamesForInitialCatalogValidation() {
		$feed = self::DATA.'/feed.xml';
		$tl = $this->bbs->tagsNamesForInitial('B');
		$xml = $this->gen->tagsNamesForInitialCatalog($feed,$tl, 'B');
		$this->assertTrue(file_exists($feed));		
		$this->assertTrue($this->opdsValidateSchema($feed));
		$this->assertTrue($this->opdsValidate($feed,'1.0'));
		$this->assertTrue($this->opdsValidate($feed,'1.1'));
	}

	function testTagsBooksForTagCatalogValidation() {
		$feed = self::DATA.'/feed.xml';
		$adetails = $this->bbs->tagDetails(9);
		$books = $this->bbs->titleDetailsFilteredOpds($adetails['books']);
		$xml = $this->gen->booksForTagCatalog($feed,$books, 'B', $adetails, false);
		$this->assertTrue(file_exists($feed));		
		$this->assertTrue($this->opdsValidateSchema($feed));
		$this->assertTrue($this->opdsValidate($feed,'1.0'));
		$this->assertTrue($this->opdsValidate($feed,'1.1'));
	}

}
?>
