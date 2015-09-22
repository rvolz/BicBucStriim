<?php
set_include_path("tests:vendor");
require_once('simpletest/simpletest/autorun.php');
require_once('lib/BicBucStriim/utilities.php');

class TestOfUtilities extends UnitTestCase {

	const FIXT = './tests/fixtures'; 

	function testBookPath() {
		$this->assertEqual('tests/fixtures/lib2/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub',
			Utilities::bookPath('tests/fixtures/lib2','Gotthold Ephraim Lessing/Lob der Faulheit (1)', 'Lob der Faulheit - Gotthold Ephraim Lessing.epub'));
	}

	function testTitleMimeType() {
		$this->assertEqual('application/epub+zip', Utilities::titleMimeType('x/y/test.epub'));
		$this->assertEqual('application/vnd.amazon.ebook', Utilities::titleMimeType('test.azw'));
		$this->assertEqual('application/x-mobipocket-ebook', Utilities::titleMimeType('test.mobi'));
		$this->assertEqual('text/plain', Utilities::titleMimeType(self::FIXT.'/test.unknown-format'));
		$this->assertEqual('application/xml', Utilities::titleMimeType(self::FIXT.'/atom.rng'));
	}
}
?>
