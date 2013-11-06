<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2013 Rainer Volz
 * Licensed under MIT License, see LICENSE
 * 
 */ 

require_once 'epub.php';

/**
 * Update the metadata of an EPUB file. Creates temporary copies 
 * of the orginal files, updates them and deletes them after use.
 */
class MetadataEpub {
	// Prefix for the temporyry EPUB files
	protected $prefix = "bbs_epub_";
	// The actual EPUB converter
	protected $converter;

	/**
	 * Constructor that copies the original file to a temporary location,
	 * the system's 'tmp' directory, and initializes the metadata update 
	 * process.
	 * @param string 	path to file for update
	 * @param string 	optional, directory for temporary files; 
	 *					if not defined the systems TEMP directory 
	 * 					will be used (see sys_get_temp_dir())
	 */
	public function __construct($file, $tmpdir=null) {
		if (is_null($tmpdir))
			$tmp = sys_get_temp_dir();
		else
			$tmp = $tmpdir;
		$new_epub = tempnam($tmp, $this->prefix);
		$status = copy($file, $new_epub);
		if (!$status)
			throw new Exception('Couldn\'t copy epub file');
		$this->converter = new Epub($new_epub);
	}

	/**
	 * Destructor that removes the temporary file
	 * if it still exists.
	 */
	public function __destruct() {
		$fname = $this->converter->file();
		if (!is_null($fname) && file_exists($fname));
			unlink($fname);
	}

	/**
	 * Return the path to the updated file
	 */
	public function getUpdatedFile() {
		return $this->converter->file();
	}

	/**
	 * Update a temporary copy of the file with the metadata passed.
	 *
	 * The language information is only updated if the *intl* extension
	 * is present to convert Calibre's ISO 639-2 codes to EPUB's ISO 639-1.
	 * Due to limitations in the EPUB library only the first language is 
	 * used.
	 *
	 * @param array 	Calibre metadata
	 * @param string 	path to new cover image
	 */
	public function updateMetadata($metadata=null, $cover = null) {
		if (is_null($metadata))
			return;
		// replace title
		$this->converter->Title($metadata['book']->title);
		// replace authors
		$authors = array();
		foreach ($metadata['authors'] as $author) {
			$authors[$author->sort] = $author->name;
		}
		$this->converter->Authors($authors);
		// replace language 
		$langs = $metadata['langcodes'];
		if (count($langs) > 0 && extension_loaded('intl')) {			
			$iso6391 = Locale::getPrimaryLanguage($langs[0]);
			$this->converter->Language($iso6391);
		}
		// replace IDs
		$ids = $metadata['ids'];
		if (!empty($ids['isbn']))
			$this->converter->ISBN($ids['isbn']);
		if (!empty($ids['google']))
			$this->converter->Google($ids['google']);
		if (!empty($ids['amazon']))
			$this->converter->Amazon($ids['amazon']);
		// replace subject tags
		$tags = array_map(function($tag) {return $tag->name;}, $metadata['tags']);
		$this->converter->Subjects($tags);
		// replace the cover image
		if ($cover != null) {
			$this->converter->Cover($cover, 'image/jpeg');
		}
		// replace the description
		$this->converter->Description($metadata['comment']);
		$this->converter->save();		
	}
}
?>
