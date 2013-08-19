<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2013 Rainer Volz
 * Licensed under MIT License, see LICENSE
 * 
 */ 

class CalibreFilter {
	protected $lang_id;
	protected $tag_id;

	/**
     * Initialize the filter.
     *
     * @param lang the calibre id of the language, default null
     * @param tag  the calibre id of the tag, default null
     */
    public function __construct($lang=null, $tag=null) {
        $this->lang_id = $lang;
        $this->tag_id = $tag;
    }
	
	/**
	 * Return the SQL FROM expression for the filter values. If there are none
	 * the 'filter' is simply the 'books' table.
	 * @return SQL string for FROM clause
	 */
    public function getBooksFilter() {
    	if (is_null($this->lang_id) && is_null($this->tag_id)) {
    		// no filter, just return the books table name
    		return 'books';
    	} elseif (is_null($this->lang_id) && !is_null($this->tag_id)) {
    		// filter by tag, books with this tag are not to be displayed
			return '(select * from books b where not exists (select * from books_tags_link btl where b.id=btl.book and tag='.$this->tag_id.'))';    		
    	} elseif (!is_null($this->lang_id) && is_null($this->tag_id)) {
    		// filter by language, only show books with this language
    		return '(select * from books b left join books_languages_link bll on b.id=bll.book where lang_code='.$this->lang_id.')';
    	} else {
    		// filter by language and tag, show only books with the selected language 
    		// but filter out the ones with the tag
    		return '(select * from (books b left join books_languages_link bll on b.id=bll.book) where lang_code='.$this->lang_id.' and not exists (select * from books_tags_link btl where b.id=btl.book and tag='.$this->tag_id.'))';
    	}
    }
}

?>
