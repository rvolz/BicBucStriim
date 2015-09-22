<?php
/**
 * EPUB metadata converter test suite.
 *
 */

set_include_path("tests:vendor");
require_once('simpletest/simpletest/autorun.php');
require_once('lib/BicBucStriim/calibre.php');
require_once('lib/BicBucStriim/metadata_epub.php');
class TestOfMetadataEpub extends UnitTestCase {
	const DATA = './tests/data';
	const FDIR = './tests/fixtures/';
	const CDIR = './tests/fixtures/lib2/';
	const CDB2 = './tests/fixtures/lib2/metadata.db';

	var $calibre;

	function setUp() {
		if (file_exists(self::DATA))
			system("rm -rf ".self::DATA);	
	    mkdir(self::DATA);
    	chmod(self::DATA,0777);
    	$this->calibre = new Calibre(self::CDB2);
	}

	function tearDown() {
		$this->calibre = NULL;
		system("rm -rf ".self::DATA);
	}

	function compareCover($bookFile, $imageFile) {
		$conv2 = new Epub($bookFile);
		$imageData = $conv2->Cover();
		$byteArray = unpack("C*",$imageData['data']); 
		$dsize = count($byteArray);
		$handle = fopen($imageFile, "r");
		$fsize = filesize($imageFile);
		$contents = fread($handle, $fsize);
		fclose($handle);
		//printf("compareCover epub cover size %d\n", $dsize);
		//printf("compareCover image cover size %d\n", $fsize);
		if ($dsize != $fsize)
			return false;
		return true;
	}

	function testConstructStdDir() {
		$orig = self::CDIR.'/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
		$conv = new MetadataEpub($orig);
		$tmpfile = $conv->getUpdatedFile();
		$this->assertTrue(file_exists($tmpfile));
		$parts = pathinfo($tmpfile);
		$this->assertEqual(sys_get_temp_dir(), $parts['dirname']);
		$this->assertEqual(filesize($orig), filesize($tmpfile));
	}

	function testConstructOtherDir() {
		$orig = self::CDIR.'/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
		$conv = new MetadataEpub($orig, self::DATA);
		$tmpfile = $conv->getUpdatedFile();
		$this->assertTrue(file_exists($tmpfile));
		$parts = pathinfo($tmpfile);
		$this->assertEqual(realpath(self::DATA), $parts['dirname']);
		$this->assertEqual(filesize($orig), filesize($tmpfile));
	}

	function testDestruct() {
		$orig = self::CDIR.'/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
		$conv = new MetadataEpub($orig);
		$tmpfile = $conv->getUpdatedFile();
		$this->assertTrue(file_exists($tmpfile));
		$conv = null;
		$this->assertFalse(file_exists($tmpfile));
	}

	function testUpdateMetadataTitle() {
		$orig = self::CDIR.'/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
		$new_title = 'Kein Lob der Faulheit';
		$conv = new MetadataEpub($orig);
		$md = $this->calibre->titleDetails('de', 1);
		$md['book']->title = $new_title;
		$conv->updateMetadata($md);
		
		$tmpfile = $conv->getUpdatedFile();
		$check = new Epub($tmpfile);
		$this->assertEqual($new_title, $check->Title());
	}

	function testUpdateMetadataAuthors() {
		$orig = self::CDIR.'/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
		$new_author = new Author();
		$new_author->sort = 'Lastname, Firstname';
		$new_author->name = 'Firstname Lastname';
		$conv = new MetadataEpub($orig);
		$md = $this->calibre->titleDetails('de', 1);
		array_unshift($md['authors'], $new_author);
		$conv->updateMetadata($md);
		
		$tmpfile = $conv->getUpdatedFile();
		$check = new Epub($tmpfile);
		$authors_check = $check->Authors();
		$this->assertEqual(2, count($authors_check));
		$this->assertEqual('Firstname Lastname', $authors_check['Lastname, Firstname']);
		$this->assertEqual('Gotthold Ephraim Lessing', $authors_check['Lessing, Gotthold Ephraim']);
	}

	function testUpdateMetadataLanguage() {
		$orig = self::CDIR.'/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
		$new_lang = 'eng';
		$conv = new MetadataEpub($orig);
		$md = $this->calibre->titleDetails('de', 1);
		$md['langcodes'][0] = $new_lang;
		$conv->updateMetadata($md);
		
		$tmpfile = $conv->getUpdatedFile();
		$check = new Epub($tmpfile);
		if (extension_loaded('intl'))
			$this->assertEqual('en', $check->Language());
		else
			$this->assertEqual('de', $check->Language());
	}

	function testUpdateMetadataMultipleLanguages() {
		$orig = self::CDIR.'/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
		$conv = new MetadataEpub($orig);
		$md = $this->calibre->titleDetails('de', 1);
		$md['langcodes'][0] = 'eng';
		$md['langcodes'][1] = 'lat';
		$conv->updateMetadata($md);
		
		$tmpfile = $conv->getUpdatedFile();
		$check = new Epub($tmpfile);
		if (extension_loaded('intl'))
			$this->assertEqual('en', $check->Language());
		else
			$this->assertEqual('de', $check->Language());
	}

	function testUpdateId() {
		$orig = self::CDIR.'/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
		$conv = new MetadataEpub($orig);
		$md = $this->calibre->titleDetails('de', 1);
		$md['ids']['isbn'] = '000000';
		$md['ids']['google'] = '111111';
		$md['ids']['amazon'] = '222222';
		$conv->updateMetadata($md);
		
		$tmpfile = $conv->getUpdatedFile();
		$check = new Epub($tmpfile);
		$this->assertEqual('000000', $check->ISBN());
		$this->assertEqual('111111', $check->Google());
		$this->assertEqual('222222', $check->Amazon());
	}

	function testUpdateMetadataTags() {
		$orig = self::CDIR.'/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
		$new_tag1 = new Tag();
		$new_tag1->name = 'Subject 1';
		$new_tag2 = new Tag();
		$new_tag2->name = 'Subject 2';
		$conv = new MetadataEpub($orig);
		$md = $this->calibre->titleDetails('de', 1);
		$md['tags'] = array($new_tag1, $new_tag2);
		$conv->updateMetadata($md);
		
		$tmpfile = $conv->getUpdatedFile();
		$check = new Epub($tmpfile);
		$subjects_check = $check->Subjects();
		$this->assertEqual(2, count($subjects_check));
		$this->assertEqual('Subject 1', $subjects_check[0]);
		$this->assertEqual('Subject 2', $subjects_check[1]);
	}

	function testUpdateMetadataCover() {
		$orig = self::CDIR.'/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
		$cover = self::FDIR.'test-cover.jpg';
		$cover2 = self::CDIR.'/Gotthold Ephraim Lessing/Lob der Faulheit (1)/cover.jpg';
		$md = $this->calibre->titleDetails('de', 1);
		$conv = new MetadataEpub($orig);
		$conv->updateMetadata($md, $cover);
		$tmpfile = $conv->getUpdatedFile();		
		$this->assertTrue($this->compareCover($tmpfile, $cover));
		$this->assertFalse($this->compareCover($tmpfile, $cover2));
	}

	function testUpdateMetadataDescription() {
		$orig = self::CDIR.'/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
		$new_desc = '<div><p>Kein Lob der Faulheit</p></div>';
		$conv = new MetadataEpub($orig);
		$md = $this->calibre->titleDetails('de', 1);
		$md['comment'] = $new_desc;
		$conv->updateMetadata($md);
		
		$tmpfile = $conv->getUpdatedFile();
		$check = new Epub($tmpfile);
		$this->assertEqual($new_desc, $check->Description());
	}


}
?>