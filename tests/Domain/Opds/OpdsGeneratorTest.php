<?php

namespace Tests\Domain\Opds;

use App\Domain\BicBucStriim\BicBucStriim;
use App\Domain\BicBucStriim\BicBucStriimRepository;
use App\Domain\BicBucStriim\L10n;
use App\Domain\Calibre\Calibre;
use App\Domain\Calibre\CalibreFilter;
use App\Domain\Calibre\CalibreRepository;
use App\Domain\Calibre\SearchOptions;
use App\Domain\Opds\OpdsGenerator;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use RedBeanPHP\R;
use SimpleXMLElement;

class OpdsGeneratorTest extends TestCase
{
    const OPDS_RNG = __DIR__ . '/../../fixtures/opds_catalog_1_1.rng';
    const DATA = __DIR__ . '/../../data';
    const DB2 = __DIR__ . '/../../fixtures/data2.db';
    const CDB2 = __DIR__ . '/../../fixtures/lib2/metadata.db';
    const DATADB = __DIR__ . '/../../data/data.db';

    var ?BicBucStriimRepository $bbs;
    var OpdsGenerator $gen;
    var ?CalibreRepository $calibre;

    function setUp(): void
    {
        global $langen;
        if (file_exists(self::DATA))
            system("rm -rf ".self::DATA);
        mkdir(self::DATA);
        chmod(self::DATA,0777);
        copy(self::DB2, self::DATADB);
        $this->bbs = new BicBucStriim(self::DATADB);
        $this->calibre = new Calibre(self::CDB2);
        $l10n = new L10n('en');
        $this->gen = new OpdsGenerator('/bbs', '0.9.0',
            $this->calibre->calibre_dir,
            date(DATE_ATOM, strtotime('2012-01-01T11:59:59')),
            $l10n);
    }

    function tearDown(): void
    {
        $this->calibre = null;
        R::nuke();
        R::close();
        $this->bbs = null;
        system("rm -rf ".self::DATA);
    }

    # Validation helper: validate relaxng
    function opdsValidateSchema($feed): bool
    {
        $res = system('java -jar /usr/share/java/jing.jar '.self::OPDS_RNG.' '.$feed);
        if ($res != '') {
            echo 'RelaxNG validation error: '.$res;
            return false;
        } else
            return true;
    }

    // Validation helper: validate opds
    // TODO find validator jar, doesn't build
    function opdsValidate($feed, $version): bool
    {
        return true;
        /*
        $cmd = 'cd ~/lib/java/opds-validator;java -jar OPDSValidator.jar -v'.$version.' '.realpath($feed);
        $res = system($cmd);
        if ($res != '') {
            echo 'OPDS validation error: '.$res;
            return false;
        } else
            return true;
        */
    }

    # Timestamp helper: generate proper timezone offsets
    # see http://www.php.net/manual/en/datetimezone.getoffset.php
    function genTimestampOffset($phpTime) {
        if(date_default_timezone_get() == 'UTC') {
            $offsetString = '+00:00'; // No need to calculate offset, as default timezone is already UTC
        } else {
            $millis = strtotime($phpTime); // Convert time to milliseconds since 1970, using default timezone
            $timezone = new DateTimeZone(date_default_timezone_get()); // Get default system timezone to create a new DateTimeZone object
            $offset = $timezone->getOffset(new DateTime($phpTime)); // Offset in seconds to UTC
            $offsetHours = round(abs($offset)/3600);
            $offsetMinutes = round((abs($offset) - $offsetHours * 3600) / 60);
            $offsetString = ($offset < 0 ? '-' : '+')
                . ($offsetHours < 10 ? '0' : '') . $offsetHours
                . ':'
                . ($offsetMinutes < 10 ? '0' : '') . $offsetMinutes;
        }
        return $offsetString;
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
        self::markTestSkipped('re-enable when fixing OPDS');
        $expected = '<entry>
 <id>urn:bicbucstriim:/bbs/opds/titles/2</id>
 <title>Trutz Simplex</title>
 <dc:issued>2012</dc:issued>
 <updated>2012-01-01T11:59:59'.$this->genTimestampOffset('2012-01-01 11:59:59').'</updated>
 <author>
  <name>Grimmelshausen, Hans Jakob Christoffel von</name>
 </author>
 <content type="text/html"></content>
 <dc:language>deu</dc:language>
 <link href="/bbs/static/thumbnail/2/" type="image/png" rel="http://opds-spec.org/image/thumbnail"/>
 <link href="/bbs/static/covers/2/" type="image/jpeg" rel="http://opds-spec.org/image"/>
 <link href="/bbs/static/files/2/Trutz+Simplex+-+Hans+Jakob+Christoffel+von+Grimmelshausen.epub" type="application/epub+zip" rel="http://opds-spec.org/acquisition"/>
 <link href="/bbs/static/files/2/Trutz+Simplex+-+Hans+Jakob+Christoffel+von+Grimmelshausen.mobi" type="application/x-mobipocket-ebook" rel="http://opds-spec.org/acquisition"/>
 <category term="Biografien &amp; Memoiren" label="Biografien &amp; Memoiren"/>
</entry>
';
        $just_book = $this->calibre->title(2);
        #print_r($just_book);
        $book = $this->calibre->titleDetailsOpds($just_book);
        $this->gen->openStream(null);
        $this->gen->partialAcquisitionEntry($book, false);
        $result = $this->gen->closeStream();
        #print_r($result);
        $this->assertEquals($expected, $result);
    }

    function testPartialAcquisitionEntryWithProtection() {
        self::markTestSkipped('re-enable when fixing OPDS');
        $expected = '<entry>
 <id>urn:bicbucstriim:/bbs/opds/titles/2</id>
 <title>Trutz Simplex</title>
 <dc:issued>2012</dc:issued>
 <updated>2012-01-01T11:59:59'.$this->genTimestampOffset('2012-01-01T11:59:59').'</updated>
 <author>
  <name>Grimmelshausen, Hans Jakob Christoffel von</name>
 </author>
 <content type="text/html"></content>
 <dc:language>deu</dc:language>
 <link href="/bbs/static/thumbnails/titles/2/thumbnail/" type="image/png" rel="http://opds-spec.org/image/thumbnail"/>
 <link href="/bbs/static/covers/2/" type="image/jpeg" rel="http://opds-spec.org/image"/>
 <link href="/bbs/static/titles/2/showaccess/" type="text/html" rel="http://opds-spec.org/acquisition">
  <opds:indirectAcquisition type="application/epub+zip"/>
 </link>
 <link href="/bbs/opds/titles/2/showaccess/" type="text/html" rel="http://opds-spec.org/acquisition">
  <opds:indirectAcquisition type="application/x-mobipocket-ebook"/>
 </link>
 <category term="Biografien &amp; Memoiren" label="Biografien &amp; Memoiren"/>
</entry>
';
        $just_book = $this->calibre->title(2);
        $book = $this->calibre->titleDetailsOpds($just_book);
        $this->gen->openStream(null);
        $this->gen->partialAcquisitionEntry($book, true);
        $result = $this->gen->closeStream();
        #print_r($result);
        $this->assertEquals($expected, $result);
    }

    function testNewestCatalogValidation() {
        $feed = self::DATA.'/feed.xml';
        $just_books = $this->calibre->last30Books('en', 30, new CalibreFilter());
        $books = $this->calibre->titleDetailsFilteredOpds($just_books);
        $xml = $this->gen->newestCatalog($feed,$books,false);
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed,'1.0'));
        $this->assertTrue($this->opdsValidate($feed,'1.1'));
    }

    function testTitlesCatalogValidation() {
        $feed = self::DATA.'/feed.xml';
        $tl = $this->calibre->titlesSlice('en', 0, 2, new CalibreFilter());
        $books = $this->calibre->titleDetailsFilteredOpds($tl['entries']);
        $xml = $this->gen->titlesCatalog($feed,$books,false,
            $tl['page'],$tl['page']+1,$tl['pages']-1);
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed,'1.0'));
        $this->assertTrue($this->opdsValidate($feed,'1.1'));
    }

    function testTitlesCatalogOpenSearch() {
        $tl = $this->calibre->titlesSlice('en', 0, 2, new CalibreFilter());
        $books = $this->calibre->titleDetailsFilteredOpds($tl['entries']);
        $xml = $this->gen->titlesCatalog(null,$books,false,
            $tl['page'],$tl['page']+1,$tl['pages']-1);
        $feed = new SimpleXMLElement($xml);
        $this->assertEquals(7,count($feed->link));
        $oslnk = $feed->link[0];
        $this->assertEquals(OpdsGenerator::OPENSEARCH_MIME,(string)$oslnk['type']);
        $this->assertTrue(strpos((string)$oslnk['href'],'opensearch.xml')>0);
    }


    function testAuthorsInitialCatalogValidation() {
        $feed = self::DATA.'/feed.xml';
        $tl = $this->calibre->authorsInitials(SearchOptions::genEmpty());
        $xml = $this->gen->authorsRootCatalog($feed,$tl);
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed,'1.0'));
        $this->assertTrue($this->opdsValidate($feed,'1.1'));
    }

    function testAuthorsNamesForInitialCatalogValidation() {
        $feed = self::DATA.'/feed.xml';
        $tl = $this->calibre->authorsNamesForInitial('R');
        $xml = $this->gen->authorsNamesForInitialCatalog($feed,$tl, 'R');
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed,'1.0'));
        $this->assertTrue($this->opdsValidate($feed,'1.1'));
    }

    function testAuthorsBooksForAuthorCatalogValidation() {
        $feed = self::DATA.'/feed.xml';
        $adetails = $this->calibre->authorDetails(5);
        $books = $this->calibre->titleDetailsFilteredOpds($adetails['books']);
        $xml = $this->gen->booksForAuthorCatalog($feed, $books, 'E', $adetails['author'], false, 0, 1, 2);
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed,'1.0'));
        $this->assertTrue($this->opdsValidate($feed,'1.1'));
    }

    function testTagsInitialCatalogValidation() {
        $feed = self::DATA.'/feed.xml';
        $tl = $this->calibre->tagsInitials(SearchOptions::genEmpty());
        $xml = $this->gen->tagsRootCatalog($feed,$tl);
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed,'1.0'));
        $this->assertTrue($this->opdsValidate($feed,'1.1'));
    }

    function testTagsNamesForInitialCatalogValidation() {
        $feed = self::DATA.'/feed.xml';
        $tl = $this->calibre->tagsNamesForInitial('B');
        $xml = $this->gen->tagsNamesForInitialCatalog($feed,$tl, 'B');
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed,'1.0'));
        $this->assertTrue($this->opdsValidate($feed,'1.1'));
    }

    function testTagsBooksForTagCatalogValidation() {
        $feed = self::DATA.'/feed.xml';
        $adetails = $this->calibre->tagDetails(9);
        $books = $this->calibre->titleDetailsFilteredOpds($adetails['books']);
        $xml = $this->gen->booksForTagCatalog($feed,$books, 'B', $adetails['tag'], false, 0, 1, 2);
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed,'1.0'));
        $this->assertTrue($this->opdsValidate($feed,'1.1'));
    }

    function testSeriesInitialCatalogValidation() {
        $feed = self::DATA.'/feed.xml';
        $tl = $this->calibre->seriesInitials(SearchOptions::genEmpty());
        $xml = $this->gen->seriesRootCatalog($feed,$tl);
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed,'1.0'));
        $this->assertTrue($this->opdsValidate($feed,'1.1'));
    }

    function testSeriesNamesForInitialCatalogValidation() {
        $feed = self::DATA.'/feed.xml';
        $tl = $this->calibre->seriesNamesForInitial('S');
        $xml = $this->gen->seriesNamesForInitialCatalog($feed,$tl, 'S');
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed,'1.0'));
        $this->assertTrue($this->opdsValidate($feed,'1.1'));
    }

    function testSeriesBooksForSeriesCatalogValidation() {
        $feed = self::DATA.'/feed.xml';
        $adetails = $this->calibre->seriesDetails(1);
        $books = $this->calibre->titleDetailsFilteredOpds($adetails['books']);
        $xml = $this->gen->booksForSeriesCatalog($feed,$books, 'S', $adetails['series'], false, 0, 1, 2);
        $this->assertTrue(file_exists($feed));
        $this->assertTrue($this->opdsValidateSchema($feed));
        $this->assertTrue($this->opdsValidate($feed,'1.0'));
        $this->assertTrue($this->opdsValidate($feed,'1.1'));
    }

}
