<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2013 Rainer Volz
 * Licensed under MIT License, see LICENSE
 * 
 */ 

require_once 'epub.php';
class MetadataEpub {
	protected $converter;

	public function __construct($file) {		
		$this->converter = new Epub($file);
	}


}
?>
