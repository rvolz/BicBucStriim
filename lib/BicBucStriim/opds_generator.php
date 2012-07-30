<?php
/**
 * Generator for OPDS 1.1 Catalogs of BicBucStriim
 */
class OpdsGenerator {

  # Pure navigation feeds
  const OPDS_MIME_NAV = 'application/atom+xml;profile=opds-catalog;kind=navigation';
  # Feeds with acquisition links
  const OPDS_MIME_ACQ = 'application/atom+xml;profile=opds-catalog;kind=acquisition';
  # General format for a book details entry document
  const OPDS_MIME_ENTRY = 'application/atom+xml;type=entry;profile=opds-catalog';

  var $bbs_root;
  var $bbs_version;
  var $updated;
  var $xmlw;
  /**
   * [__construct description]
   * @param string $bbs_root        Root URL for BicBucStriim, e.g. '/bbs'
   * @param string $bbs_version     BBS version 
   * @param string $calibre_modtime Modification time of Calibre library, in ATOM format
   */
  function __construct($bbs_root, $bbs_version, $calibre_modtime) {
    $this->bbs_root = $bbs_root;
    $this->bbs_version = $bbs_version;
    $this->updated = $calibre_modtime;
  }

  /**
   * Create the root OPDS catalog, which is a navigation catalog 
   * mentioning all available catalogs.
   * @param  string $output   a URI or NULL
   * @return string           if $output is a URI NULL, else the XML is returned as a string.
   */
  function rootCatalog($of=NULL) {
    $this->xmlw = new XMLWriter();    
    $this->openStream($of);
    $this->header('BicBucStriim Root Catalog', 
      'The root catalog to the contents of your Calibre library',
      'nav:root');
    # TODO Search link?
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'self');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'start');
    # Subcatalogs
    $this->navigationEntry('Most Recent 30', 'newest', 'The 30 most recent titles', '/newest/', 
      'http://opds-spec.org/sort/new');
    $this->navigationEntry('By Titles', 'titles', 'All books by title', '/titles/');
    $this->navigationEntry('By Authors', 'authors', 'All books by author', '/authors/');
    $this->navigationEntry('By Tags', 'tags', 'All books by tag', '/tags/');
    $this->footer();
    return $this->closeStream($of);
  }

  /**
   * Generate an acquisition catalog for the newest books
   * @param  string   $of=NULL   output URI or NULL for string output
   * @param  array    $entries   an array of Book
   * @param  boolean  $protected true = we need password authentication before a download
   */
  function newestCatalog($of=NULL, $entries, $protected) {
    $this->xmlw = new XMLWriter();    
    $this->openStream($of);
    $this->header('BicBucStriim Catalog: Most recent 30', 
      'The newest 30 titles of your Calibre library',
      'nav:newest');
    # TODO Search link?
    $this->acquisitionCatalogLink($this->bbs_root.'/opds/newest/','self');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'start');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'up');
    $this->acquisitionCatalogLink($this->bbs_root.'/opds/newest/','first');
    $this->acquisitionCatalogLink($this->bbs_root.'/opds/titles/', 'next');
    $this->navigationCatalogLink($this->bbs_root.'/opds/tags/', 'last');
    # Content
    foreach($entries as $entry)
      $this->partialAcquisitionEntry($entry, $protected);
    $this->footer();
    return $this->closeStream($of);
  }

  /**
   * Write a catalog entry for a book title with acquisition links
   * @param  Book     $entry      the book 
   * @param  boolean  $protected  true = use an indirect acquisition link, 
   *                              else a direct one 
   */
  function partialAcquisitionEntry($entry, $protected) {
    $this->startElement('entry');
    $this->writeElement('id','urn:bicbucstriim:'.$this->bbs_root.'/titles/'.$entry->id);
    $this->writeElement('title',$entry->title);
    $this->writeElement('dc:issued',date("Y",$entry->pubdate));
    # TODO: mod time of book?
    $this->writeElement('updated',$this->updated);
    if ($protected)
      $this->indirectDownloadLink();
    $this->endElement();
  }

  /**
   * Write an OPDS navigation entry
   * @param  string $title   title string (text)
   * @param  string $id      id detail, appended to 'urn:bicbucstriim:nav-'
   * @param  string $content content description (text)
   * @param  string $url     catalog url, appended to bbs_root.'/opds'
   * @param  string $rel     optional relation according to OPDS spec
   */
  function navigationEntry($title, $id, $content, $url, $rel = NULL) {
    $this->xmlw->startElement('entry');
    $this->xmlw->writeElement('title', $title);
    $this->xmlw->writeElement('id', 'urn:bicbucstriim:nav-'.$id);
    $this->xmlw->writeElement('updated', $this->updated);
    $this->xmlw->startElement('content');
    $this->xmlw->writeAttribute('type', 'text');
    $this->xmlw->text($content);
    $this->xmlw->endElement();  
    $this->link($this->bbs_root.'/opds'.$url, 'application/atom+xml;type=feed;profile=opds-catalog', $rel);
    $this->xmlw->endElement();  
  }

  /**
   * Start the OPDS feed
   * @param  string $title    OPDS feed title
   * @param  string $subtitle OPDS feed subtitle
   * @param  string $id       feed-specific part of the id, appendend to 'urn:bicbucstriim:'
   */
  function header($title,$subtitle,$id) {
    $this->xmlw->startDocument('1.0','UTF-8');
    $this->xmlw->startElement('feed');
    $this->xmlw->writeAttribute('xmlns', 'http://www.w3.org/2005/Atom');
    $this->xmlw->writeAttribute('xmlns:opds', 'http://opds-spec.org/2010/catalog'); 
    $this->xmlw->writeAttribute('xmlns:dc', 'http://purl.org/dc/terms/'); 
    $this->xmlw->writeElement('title',$title);
    $this->xmlw->writeElement('subtitle',$subtitle);
    $this->xmlw->startElement('author');
    $this->xmlw->writeElement('name', 'BicBucStriim '.$this->bbs_version);
    # TODO: textmulch url for feed uri
    $this->xmlw->writeElement('uri', 'http://rvolz.gihub.com/BicBucStriim');
    $this->xmlw->endElement();
    # TODO: proper urn
    $this->xmlw->writeElement('id', 'urn:bicbucstriim:'.$id);
    $this->xmlw->writeElement('updated', $this->updated);
  }

  /**
   * Close the OPDS feed
   */
  function footer() {
    $this->xmlw->endElement();
    $this->xmlw->endDocument(); 
  }


  /**
   * Write an ATOM link
   * @param  string $href           link URL
   * @param  string $type           link type
   * @param  string $rel            link rel, optional
   * @param  string $title          link title, optional
   * @param  string $indirectType   real $type for indirect acquisition links, optional
   */
  function link($href, $type, $rel=NULL, $title=NULL, $indirectType=NULL) {
    $this->xmlw->startElement('link');
    $this->xmlw->writeAttribute('href', $href);
    $this->xmlw->writeAttribute('type', $type);
    if (!is_null($rel)) 
      $this->xmlw->writeAttribute('rel',$rel);
    if (!is_null($title)) 
      $this->xmlw->writeAttribute('title',$rel);    
    if (!is_null($indirectType)) {
      $this->xmlw->startElement('opds:indirectAcquisition');
      $this->xmlw->writeAttribute('type', $indirectType);
      $this->xmlw->endElement();  
    }
    $this->xmlw->endElement();
  }

  /**
   * Link to an OPDS navigation catalog
   * @param  string $href   link URL
   * @param  string $type   link type
   * @param  string $title  link title, optional
   */
  function navigationCatalogLink($href, $rel=NULL, $title=NULL) {
    $this->link($href, self::OPDS_MIME_NAV, $rel, $title);
  }

  /**
   * Link to an OPDS acquisition catalog
   * @param  string $href   link URL
   * @param  string $type   link type
   * @param  string $title  link title, optional
   */
  function acquisitionCatalogLink($href, $rel=NULL, $title=NULL) {
    $this->link($href, self::OPDS_MIME_ACQ, $rel, $title);
  }

  /**
   * Link to a thumbnail pic
   * @param  string $href link URL for thumbnail
   */
  function thumbnailLink($href) {
    $this->link($href, 'image/png', 'http://opds-spec.org/image/thumbnail');
  }

  /**
   * Link to a full image
   * @param  string $href link URL for image
   */
  function imageLink($href) {
    $this->link($href, 'image/png', 'http://opds-spec.org/image/image');
  }

  /**
   * Link to a OPDS entry document with the complete book details
   * @param  string $href   link URL
   * @param  string $title  link title
   */
  function detailsLink($href, $title) {
    $this->link($href, self::OPDS_MIME_ENTRY, 'alternate', $title);
  }

  /**
   * Link directly to the downloadable ressource
   * @param  string $href link URL
   * @param  string $type MIME type of book
   */
  function directDownloadLink($href, $type) {
    $this->link($href, $type, 'http://opds-spec.org/acquisition');
  }

  /**
   * Link indirectly to the downloadable ressource, to allow for authentication via HTML
   * @param  string $href link URL
   * @param  string $type MIME type of book
   */
  function indirectDownloadLink($href, $type) {
    $this->link($href, 'text/html', 'http://opds-spec.org/acquisition',NULL,$type);
  }

  /**
   * Open and initialize the XML stream
   * @param  string $of=NULL URI or NULL
   */
  function openStream($of=NULL) {
    if (is_null($of))
      $this->xmlw->openMemory();
    else  
      $this->xmlw->openURI($of);
    $this->xmlw->setIndent(true);
  }

  /**
   * Close the XML stream and output the result
   * @param  string $of=NULL Proper URI or NULL
   * @return string          The XML string or NULL if output is sent to the URI
   */
  function closeStream($of=NULL) {
    if (is_null($of)) 
      return $this->xmlw->outputMemory(TRUE);
    else  
      return NULL;
  }

}

?>