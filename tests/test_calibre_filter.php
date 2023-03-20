<?php

set_include_path("tests:vendor");
require_once('simpletest/simpletest/autorun.php');
require_once('lib/BicBucStriim/calibre_filter.php');

class TestOfCalibreFilter extends UnitTestCase
{
    public function setUp()
    {
    }

    public function tearDown()
    {
    }

    ##
    # No filter values - the raw table name is returned
    #
    public function testNoFilter()
    {
        $filter = new CalibreFilter();
        $this->assertEqual('books', $filter->getBooksFilter());
    }

    ##
    # Language filter
    #
    public function testLanguageFilter()
    {
        $filter = new CalibreFilter($lang=1);
        $this->assertEqual('(select * from books b left join books_languages_link bll on b.id=bll.book where lang_code=:lang)', $filter->getBooksFilter());
    }

    ##
    # Tag filter
    #
    public function testTagFilter()
    {
        $filter = new CalibreFilter($lang=null, $tag=1);
        $this->assertEqual('(select * from books b where not exists (select * from books_tags_link btl where b.id=btl.book and tag=:tag))', $filter->getBooksFilter());
    }

    ##
    # Both filter values
    #
    public function testLanguageAndTagFilter()
    {
        $filter = new CalibreFilter($lang=1, $tag=1);
        $this->assertEqual('(select * from (books b left join books_languages_link bll on b.id=bll.book) where lang_code=:lang and not exists (select * from books_tags_link btl where b.id=btl.book and tag=:tag))', $filter->getBooksFilter());
    }
}
