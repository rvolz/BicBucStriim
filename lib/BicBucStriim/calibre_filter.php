<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2013 Rainer Volz
 * Licensed under MIT License, see LICENSE
 * 
 */ 

class CalibreFilter {
	/**
	 * @var string Calibre language ID
	 */
	public $lang_id = null;
	/**
	 * @var string Calibre tag ID
	 */
	public $tag_id = null;

	/**
     * Initialize the filter.
     *
     * @param string $lang the calibre id of the language, default null
     * @param string $tag  the calibre id of the tag, default null
     */
    public function __construct($lang=null, $tag=null) {
        $this->lang_id = $lang;
        $this->tag_id = $tag;
    }
	
	/**
	 * Return the SQL FROM expression for the filter values. If there are none
	 * the 'filter' is simply the 'books' table.
	 * @return string SQL string for FROM clause
	 */
    public function getBooksFilter() {
    	if (is_null($this->lang_id) && is_null($this->tag_id)) {
    		// no filter, just return the books table name
    		return 'books';
    	} elseif (is_null($this->lang_id) && !is_null($this->tag_id)) {
    		// filter by tag, books with this tag are not to be displayed
			return '(select * from books b where not exists (select * from books_tags_link btl where b.id=btl.book and tag=:tag))';
    	} elseif (!is_null($this->lang_id) && is_null($this->tag_id)) {
    		// filter by language, only show books with this language
    		return '(select * from books b left join books_languages_link bll on b.id=bll.book where lang_code=:lang)';
    	} else {
    		// filter by language and tag, show only books with the selected language 
    		// but filter out the ones with the tag
    		return '(select * from (books b left join books_languages_link bll on b.id=bll.book) where lang_code=:lang and not exists (select * from books_tags_link btl where b.id=btl.book and tag=:tag))';
    	}
    }
}

?>
