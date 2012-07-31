<?php
require_once 'utilities.php';
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
  var $calibre_dir;
  var $updated;
  var $xmlw;
  /**
   * [__construct description]
   * @param string $bbs_root        Root URL for BicBucStriim, e.g. '/bbs'
   * @param string $bbs_version     BBS version 
   * @param string $calibre_dir     calibre library dir
   * @param string $calibre_modtime Modification time of Calibre library, in ATOM format
   */
  function __construct($bbs_root, $bbs_version, $calibre_dir, $calibre_modtime) {
    $this->bbs_root = $bbs_root;
    $this->bbs_version = $bbs_version;
    $this->calibre_dir = $calibre_dir;
    $this->updated = $calibre_modtime;
  }

  /**
   * Create the root OPDS catalog, which is a navigation catalog 
   * mentioning all available catalogs.
   * @param  string $output   a URI or NULL
   * @return string           if $output is a URI NULL, else the XML is returned as a string.
   */
  function rootCatalog($of=NULL) {
    $this->openStream($of);
    $this->header('BicBucStriim Root Catalog', 
      'The root catalog to the contents of your Calibre library',
      '/opds/');
    # TODO Search link?
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'self');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'start');
    $this->link($this->bbs_root.'/opds/opensearch.xml', 'application/opensearchdescription+xml', 'search', 'Search in BicBucStriim');
    # Subcatalogs
    $this->navigationEntry('Most Recent 30', '/opds/newest/', 'The 30 most recent titles', '/newest/', 
      self::OPDS_MIME_ACQ, 'http://opds-spec.org/sort/new');
    $this->navigationEntry('By Titles', '/opds/titleslist/0/', 'All books by title', '/titleslist/0/',
      self::OPDS_MIME_ACQ);
    $this->navigationEntry('By Authors', '/opds/authorslist/', 'All books by author', '/authorslist/',
      self::OPDS_MIME_NAV);
    $this->navigationEntry('By Tags', '/opds/tagslist/', 'All books by tag', '/tagslist/',
      self::OPDS_MIME_NAV);
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
    $this->openStream($of);
    $this->header('BicBucStriim Catalog: Most recent 30', 
      'The newest 30 titles of your Calibre library',
      '/opds/newest/');
    # TODO Search link?
    $this->acquisitionCatalogLink($this->bbs_root.'/opds/newest/','self');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'start');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'up');
    # Content
    foreach($entries as $entry)
      $this->partialAcquisitionEntry($entry, $protected);
    $this->footer();
    return $this->closeStream($of);
  }

  /**
   * Generate an paginated acquisition catalog for the all books
   * @param  string   $of=NULL   output URI or NULL for string output
   * @param  array    $entries   an array of Book
   * @param  boolean  $protected true = we need password authentication before a download
   * @param  int      $page      number of page to show, minimum 0
   * @param  int      $next      number of the nextPage to show, or NULL
   * @param  int      $last      number of the last page
   */
  function titlesCatalog($of=NULL, $entries, $protected, $page, $next, $last) {
    $this->openStream($of);
    $this->header('BicBucStriim Catalog: All Titles', 
      'All books of your Calibre library, sorted by title',
      '/opds/titles/');
    # TODO Search link?
    $this->acquisitionCatalogLink($this->bbs_root.'/opds/titleslist/'.$page.'/','self');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'start');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'up');
    $this->acquisitionCatalogLink($this->bbs_root.'/opds/titleslist/0/','first');
    if (!is_null($next))
      $this->acquisitionCatalogLink($this->bbs_root.'/opds/titleslist/'.$next.'/','next');
    $this->navigationCatalogLink($this->bbs_root.'/opds/titleslist/'.$last.'/', 'last');
    # Content
    foreach($entries as $entry)
      $this->partialAcquisitionEntry($entry, $protected);
    $this->footer();
    return $this->closeStream($of);
  }

  /**
   * Generate a list of initials of author names
   * @param  string   $of=NULL   output URI or NULL for string output
   * @param  array    $entries   an array of Items
   */
  function authorsRootCatalog($of=NULL, $entries) {
    $this->openStream($of);
    $this->header('BicBucStriim Catalog: All Authors', 
      'Authors by their initials',
      '/opds/authorslist/');
    # TODO Search link?
    $this->navigationCatalogLink($this->bbs_root.'/opds/authorslist/','self');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'start');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'up');
    # Content
    foreach($entries as $entry) {
      $url = '/authorslist/'.$entry->initial.'/';
      $this->navigationEntry($entry->initial, $url, 'Authors: '.$entry->ctr, $url, 
        self::OPDS_MIME_NAV);
    }
    $this->footer();
    return $this->closeStream($of);
  }

  /**
   * generate a list of author entries with book counts
   * @param  string   $of=NULL   output URI or NULL for string output
   * @param  array    $entries   an array of Authors
   * @param  string   $initial   the initial character
   */
  function authorsNamesForInitialCatalog($of=NULL, $entries, $initial) {
    $this->openStream($of);
    $url= '/authorslist/'.$initial.'/';
    $this->header('BicBucStriim Catalog: All Authors for '.$initial, 
      'Authors list',
      $url);
    # TODO Search link?
    $this->navigationCatalogLink($this->bbs_root.$url,'self');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'start');
    $this->navigationCatalogLink($this->bbs_root.'/opds/authorslist/', 'up');
    # TODO next/prev

    # Content
    foreach($entries as $entry) {
      $url2 = $url.$entry->id.'/';
      $this->navigationEntry($entry->name, $url2, 'Books: '.$entry->anzahl, $url2, 
        self::OPDS_MIME_NAV);
    }
    $this->footer();
    return $this->closeStream($of);
  }

  /**
   * generate a list of book entries for an author
   * @param  string   $of=NULL    output URI or NULL for string output
   * @param  array    $entries    an array of Books
   * @param  string   $initial    the initial character
   * @param  string   $author     the author
   * @param  bool     $protected  download protection y/n?
   */
  function booksForAuthorCatalog($of=NULL, $entries, $initial, $author, $protected) {
    $this->openStream($of);
    $url= '/authorslist/'.$initial.'/'.$author->id.'/';
    $this->header('BicBucStriim Catalog: '.$author->name, 
      'All books by '.$author->name,
      $url);
    $this->acquisitionCatalogLink($this->bbs_root.$url,'self');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'start');
    $this->navigationCatalogLink($this->bbs_root.'/opds/authorslist/'.$initial.'/', 'up');
    # Content
    foreach($entries as $entry) {
      $url2 = $url.$entry['book']->id.'/';
      $this->partialAcquisitionEntry($entry, $protected);
    }
    $this->footer();
    return $this->closeStream($of);
  }


  /**
   * Write a catalog entry for a book title with acquisition links
   * @param  array    $entry      the book and its details 
   * @param  boolean  $protected  true = use an indirect acquisition link, 
   *                              else a direct one 
   */
  function partialAcquisitionEntry($entry, $protected) {
    $titleLink = $this->bbs_root.'/titles/'.$entry['book']->id;
    $this->xmlw->startElement('entry');
    $this->xmlw->writeElement('id','urn:bicbucstriim:'.$titleLink);
    $this->xmlw->writeElement('title',$entry['book']->title);
    $this->xmlw->writeElement('dc:issued',date("Y",strtotime($entry['book']->pubdate)));    
    $this->xmlw->writeElement('updated',$this->updated);
    $this->xmlw->startElement('author');
    $this->xmlw->writeElement('name',$entry['book']->author_sort);
    $this->xmlw->endElement();    
    $this->thumbnailLink($titleLink.'/thumbnail/');
    #$this->detailsLink($titleLink.'/thumbnail/');
    foreach($entry['formats'] as $format) {
      $fname = $format->name;
      $ext = strtolower($format->format);
      $bp = Utilities::bookPath($this->calibre_dir,$entry['book']->path,$fname.'.'.$ext);
      $mt = Utilities::titleMimeType($bp);
      if ($protected)
        $this->indirectDownloadLink($titleLink.'/showaccess/', $mt);
       else      
        $this->directDownloadLink($titleLink.'/file/'.urlencode($fname).'.'.$ext,$mt);
    }

    $this->xmlw->endElement();
  }

  /**
   * Write an OPDS navigation entry
   * @param  string $title   title string (text)
   * @param  string $id      id detail, appended to 'urn:bicbucstriim:nav-'
   * @param  string $content content description (text)
   * @param  string $url     catalog url, appended to bbs_root.'/opds'
   * @param  string type     navigation or acquisition feed
   * @param  string $rel     optional relation according to OPDS spec
   */
  function navigationEntry($title, $id, $content, $url, $type, $rel='subsection') {
    $this->xmlw->startElement('entry');
    $this->xmlw->writeElement('title', $title);
    $this->xmlw->writeElement('id', $this->bbs_root.$id);
    $this->xmlw->writeElement('updated', $this->updated);
    $this->xmlw->startElement('content');
    $this->xmlw->writeAttribute('type', 'text');
    $this->xmlw->text($content);
    $this->xmlw->endElement();  
    $this->link($this->bbs_root.'/opds'.$url, $type, $rel);
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
    $this->xmlw->writeAttribute('xmlns:opds', 'http://opds-spec.org/2010/catalog'); 
    $this->xmlw->writeAttribute('xmlns:dc', 'http://purl.org/dc/terms/'); 
    $this->xmlw->writeAttribute('xmlns:thr','http://purl.org/syndication/thread/1.0');
    $this->xmlw->writeAttribute('xmlns:opensearch','http://a9.com/-/spec/opensearch/1.1/');
    $this->xmlw->writeAttribute('xml:lang','en');
    $this->xmlw->writeAttribute('xmlns', 'http://www.w3.org/2005/Atom');    
    $this->xmlw->writeElement('title',$title);
    $this->xmlw->writeElement('subtitle',$subtitle);
    $this->xmlw->writeElement('icon',$this->bbs_root.'/favicon.ico');
    $this->xmlw->startElement('author');
    $this->xmlw->writeElement('name', 'BicBucStriim '.$this->bbs_version);
    # TODO: textmulch url for feed uri
    $this->xmlw->writeElement('uri', 'http://rvolz.gihub.com/BicBucStriim');
    $this->xmlw->endElement();
    # TODO: proper urn
    #$this->xmlw->writeElement('id', 'urn:bicbucstriim:'.$id);
    $this->xmlw->writeElement('id', $this->bbs_root.$id);
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
    $this->xmlw = new XMLWriter();        
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