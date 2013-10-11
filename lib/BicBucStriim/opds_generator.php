<?php
require_once 'utilities.php';
require_once 'l10n.php';
/**
 * Generator for OPDS 1.1 Catalogs of BicBucStriim
 */
class OpdsGenerator {

  # ATOM catalog
  const ATOM_CATALOG = 'application/atom+xml';
  # Common catalog
  const OPDS_MIME_CATALOG = 'application/atom+xml;profile=opds-catalog';
  # Pure navigation feeds
  const OPDS_MIME_NAV = 'application/atom+xml;profile=opds-catalog;kind=navigation';
  # Feeds with acquisition links
  const OPDS_MIME_ACQ = 'application/atom+xml;profile=opds-catalog;kind=acquisition';
  # General format for a book details entry document
  const OPDS_MIME_ENTRY = 'application/atom+xml;type=entry;profile=opds-catalog';
  # OpenSearch 
  const OPENSEARCH_MIME = 'application/opensearchdescription+xml';

  var $bbs_root;
  var $bbs_version;
  var $calibre_dir;
  var $updated;
  var $xmlw;
  var $l10n;
  /**
   * [__construct description]
   * @param string $bbs_root        Root URL for BicBucStriim, e.g. '/bbs'
   * @param string $bbs_version     BBS version 
   * @param string $calibre_dir     calibre library dir
   * @param string $calibre_modtime Modification time of Calibre library, in ATOM format
   * @param object $l10n            Initialized localization helper
   */
  function __construct($bbs_root, $bbs_version, $calibre_dir, $calibre_modtime, $l10n=NULL) {
    $this->bbs_root = $bbs_root;
    $this->bbs_version = $bbs_version;
    $this->calibre_dir = $calibre_dir;
    $this->updated = $calibre_modtime;
    $this->l10n = $l10n;
  }

  /**
   * Create the root OPDS catalog, which is a navigation catalog 
   * mentioning all available catalogs.
   * @param  string $output   a URI or NULL
   * @return string           if $output is a URI NULL, else the XML is returned as a string.
   */
  function rootCatalog($of=NULL) {
    $this->openStream($of);
    $this->header('opds_root_title', 
      'opds_root_subtitle',
      '/opds/');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'self');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'start');    
    # Subcatalogs
    $this->navigationEntry($this->l10n->message('opds_by_newest1'), '/opds/newest/', $this->l10n->message('opds_by_newest2'), '/newest/', 
      self::OPDS_MIME_ACQ, 'http://opds-spec.org/sort/new');
    $this->navigationEntry($this->l10n->message('opds_by_title1'), '/opds/titleslist/0/', $this->l10n->message('opds_by_title2'), '/titleslist/0/',
      self::OPDS_MIME_ACQ);
    $this->navigationEntry($this->l10n->message('opds_by_author1'), '/opds/authorslist/', $this->l10n->message('opds_by_author2'), '/authorslist/',
      self::OPDS_MIME_NAV);
    $this->navigationEntry($this->l10n->message('opds_by_tag1'), '/opds/tagslist/', $this->l10n->message('opds_by_tag2'), '/tagslist/',
      self::OPDS_MIME_NAV);
    $this->navigationEntry($this->l10n->message('opds_by_series1'), '/opds/serieslist/', $this->l10n->message('opds_by_series2'), '/serieslist/',
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
    $this->header('opds_by_newest1', 
      'opds_by_newest2',
      '/opds/newest/');
    $this->searchLink();
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
   * Generate a paginated acquisition catalog for the all books
   * @param  string   $of=NULL   output URI or NULL for string output
   * @param  array    $entries   an array of Book
   * @param  boolean  $protected true = we need password authentication before a download
   * @param  int      $page      number of page to show, minimum 0
   * @param  int      $next      number of the next page to show, or NULL
   * @param  int      $last      number of the last page
   */
  function titlesCatalog($of=NULL, $entries, $protected, $page, $next, $last) {
    $this->openStream($of);
    $this->header('opds_by_title1', 
      'opds_by_title2',
      '/opds/titles/');
    $this->searchLink();
    $this->acquisitionCatalogLink($this->bbs_root.'/opds/titleslist/'.$page.'/','self');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'start');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'up');
    $this->acquisitionCatalogLink($this->bbs_root.'/opds/titleslist/0/','first');
    if ($page > 0)
      $this->acquisitionCatalogLink($this->bbs_root.'/opds/titleslist/'.($page-1).'/','previous');
    if (!is_null($next))
      $this->acquisitionCatalogLink($this->bbs_root.'/opds/titleslist/'.$next.'/','next');
    $this->acquisitionCatalogLink($this->bbs_root.'/opds/titleslist/'.$last.'/', 'last');
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
    $this->header('opds_by_author1', 
      'opds_by_author3',
      '/opds/authorslist/');
    $this->navigationCatalogLink($this->bbs_root.'/opds/authorslist/','self');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'start');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'up');
    # Content
    foreach($entries as $entry) {
      $url = '/authorslist/'.$entry->initial.'/';
      $this->navigationEntry($entry->initial, $url, $this->l10n->message('opds_authors').$entry->ctr, $url, 
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
    $this->header('opds_by_author4', 
      'opds_by_author5',
      $url,
      '"'.$initial.'"');
    $this->navigationCatalogLink($this->bbs_root.'/opds'.$url,'self');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'start');
    $this->navigationCatalogLink($this->bbs_root.'/opds/authorslist/', 'up');
    # TODO next/prev

    # Content
    foreach($entries as $entry) {
      $url2 = $url.$entry->id.'/0/';
      $this->navigationEntry($entry->name, $url2, $this->l10n->message('opds_books').$entry->anzahl, $url2, 
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
   * @param  int      $page      number of page to show, minimum 0
   * @param  int      $next      number of the next page to show, or NULL
   * @param  int      $last      number of the last page
   */
  function booksForAuthorCatalog($of=NULL, $entries, $initial, $author, $protected, $page, $next, $last) {
    $this->openStream($of);
    $url= '/authorslist/'.$initial.'/'.$author->id.'/';
    $this->header('opds_by_author6', 
      '',
      $url,
      '"'.$author->name.'"');
    $this->acquisitionCatalogLink($this->bbs_root.'/opds'.$url,'self');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'start');
    $this->navigationCatalogLink($this->bbs_root.'/opds/authorslist/'.$initial.'/', 'up');
    $this->acquisitionCatalogLink($this->bbs_root.'/opds/authorslist/'.$initial.'/'.$author->id.'/0/','first');
    if ($page > 0)
      $this->acquisitionCatalogLink($this->bbs_root.'/opds/authorslist/'.$initial.'/'.$author->id.'/'.($page-1).'/','previous');
    if (!is_null($next))
      $this->acquisitionCatalogLink($this->bbs_root.'/opds/authorslist/'.$initial.'/'.$author->id.'/'.$next.'/','next');
    $this->acquisitionCatalogLink($this->bbs_root.'/opds/authorslist/'.$initial.'/'.$author->id.'/'.$last.'/', 'last');
    # Content
    foreach($entries as $entry) {
      $url2 = $url.$entry['book']->id.'/';
      $this->partialAcquisitionEntry($entry, $protected);
    }
    $this->footer();
    return $this->closeStream($of);
  }

  /**
   * Generate a list of initials of tag names
   * @param  string   $of=NULL   output URI or NULL for string output
   * @param  array    $entries   an array of Items
   */
  function tagsRootCatalog($of=NULL, $entries) {
    $this->openStream($of);
    $this->header('opds_by_tag1', 
      'opds_by_tag3',
      '/opds/tagslist/');
    $this->navigationCatalogLink($this->bbs_root.'/opds/tagslist/','self');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'start');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'up');
    # Content
    foreach($entries as $entry) {
      $url = '/tagslist/'.$entry->initial.'/';
      $this->navigationEntry($entry->initial, $url, $this->l10n->message('opds_tags').$entry->ctr, $url, 
        self::OPDS_MIME_NAV);
    }
    $this->footer();
    return $this->closeStream($of);
  }

  /**
   * generate a list of tag entries with book counts
   * @param  string   $of=NULL   output URI or NULL for string output
   * @param  array    $entries   an array of Tags
   * @param  string   $initial   the initial character
   */
  function tagsNamesForInitialCatalog($of=NULL, $entries, $initial) {
    $this->openStream($of);
    $url= '/tagslist/'.$initial.'/';
    $this->header('opds_by_tag4', 
      'opds_by_tag5',
      $url,
      '"'.$initial.'"');
    $this->navigationCatalogLink($this->bbs_root.'/opds'.$url,'self');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'start');
    $this->navigationCatalogLink($this->bbs_root.'/opds/tagslist/', 'up');
    # TODO next/prev

    # Content
    foreach($entries as $entry) {
      $url2 = $url.$entry->id.'/0/';
      $this->navigationEntry($entry->name, $url2, $this->l10n->message('opds_books').$entry->anzahl, $url2, 
        self::OPDS_MIME_NAV);
    }
    $this->footer();
    return $this->closeStream($of);
  }

  /**
   * generate a list of book entries for a tag
   * @param  string   $of=NULL    output URI or NULL for string output
   * @param  array    $entries    an array of Books
   * @param  string   $initial    the initial character
   * @param  string   $tag        the tag
   * @param  bool     $protected  download protection y/n?
   * @param  int      $page      number of page to show, minimum 0
   * @param  int      $next      number of the next page to show, or NULL
   * @param  int      $last      number of the last page
   */
  function booksForTagCatalog($of=NULL, $entries, $initial, $tag, $protected, $page, $next, $last) {
    $this->openStream($of);
    $url= '/tagslist/'.$initial.'/'.$tag->id.'/';
    $this->header('opds_by_tag6', 
      '',
      $url,
      '"'.$tag->name.'"');
    $this->acquisitionCatalogLink($this->bbs_root.'/opds'.$url,'self');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'start');
    $this->navigationCatalogLink($this->bbs_root.'/opds/tagslist/'.$initial.'/', 'up');
    $this->acquisitionCatalogLink($this->bbs_root.'/opds/tagslist/'.$initial.'/'.$tag->id.'/0/','first');
    if ($page > 0)
      $this->acquisitionCatalogLink($this->bbs_root.'/opds/tagslist/'.$initial.'/'.$tag->id.'/'.($page-1).'/','previous');
    if (!is_null($next))
      $this->acquisitionCatalogLink($this->bbs_root.'/opds/tagslist/'.$initial.'/'.$tag->id.'/'.$next.'/','next');
    $this->acquisitionCatalogLink($this->bbs_root.'/opds/tagslist/'.$initial.'/'.$tag->id.'/'.$last.'/', 'last');
    # Content
    foreach($entries as $entry) {
      $url2 = $url.$entry['book']->id.'/';
      $this->partialAcquisitionEntry($entry, $protected);
    }
    $this->footer();
    return $this->closeStream($of);
  }

  /**
   * Generate a list of initials of series names
   * @param  string   $of=NULL   output URI or NULL for string output
   * @param  array    $entries   an array of Items
   */
  function seriesRootCatalog($of=NULL, $entries) {
    $this->openStream($of);
    $this->header('opds_by_series1', 
      'opds_by_series3',
      '/opds/serieslist/');
    $this->navigationCatalogLink($this->bbs_root.'/opds/serieslist/','self');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'start');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'up');
    # Content
    foreach($entries as $entry) {
      $url = '/serieslist/'.$entry->initial.'/';
      $this->navigationEntry($entry->initial, $url, $this->l10n->message('opds_series').$entry->ctr, $url, 
        self::OPDS_MIME_NAV);
    }
    $this->footer();
    return $this->closeStream($of);
  }

  /**
   * generate a list of series entries with book counts
   * @param  string   $of=NULL   output URI or NULL for string output
   * @param  array    $entries   an array of Series
   * @param  string   $initial   the initial character
   */
  function seriesNamesForInitialCatalog($of=NULL, $entries, $initial) {
    $this->openStream($of);
    $url= '/serieslist/'.$initial.'/';
    $this->header('opds_by_series4',
      'opds_by_series5',
      $url,
      '"'.$initial.'"');
    $this->navigationCatalogLink($this->bbs_root.'/opds'.$url,'self');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'start');
    $this->navigationCatalogLink($this->bbs_root.'/opds/serieslist/', 'up');
    # TODO next/prev

    # Content
    foreach($entries as $entry) {
      $url2 = $url.$entry->id.'/0/';
      $this->navigationEntry($entry->name, $url2, $this->l10n->message('opds_books').$entry->anzahl, $url2, 
        self::OPDS_MIME_NAV);
    }
    $this->footer();
    return $this->closeStream($of);
  }

  /**
   * generate a list of book entries for a series
   * @param  string   $of=NULL    output URI or NULL for string output
   * @param  array    $entries    an array of Books
   * @param  string   $initial    the initial character
   * @param  string   $tag        the series
   * @param  bool     $protected  download protection y/n?
   * @param  int      $page      number of page to show, minimum 0
   * @param  int      $next      number of the next page to show, or NULL
   * @param  int      $last      number of the last page
   */
  function booksForSeriesCatalog($of=NULL, $entries, $initial, $series, $protected, $page, $next, $last) {
    $this->openStream($of);
    $url= '/serieslist/'.$initial.'/'.$series->id.'/';
    $this->header('opds_by_series6',
      '',
      $url,
      '"'.$series->name.'"');
    $this->acquisitionCatalogLink($this->bbs_root.'/opds'.$url,'self');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'start');
    $this->navigationCatalogLink($this->bbs_root.'/opds/serieslist/'.$initial.'/', 'up');
    $this->acquisitionCatalogLink($this->bbs_root.'/opds/authorslist/'.$initial.'/'.$series->id.'/0/','first');
    if ($page > 0)
      $this->acquisitionCatalogLink($this->bbs_root.'/opds/authorslist/'.$initial.'/'.$series->id.'/'.($page-1).'/','previous');
    if (!is_null($next))
      $this->acquisitionCatalogLink($this->bbs_root.'/opds/authorslist/'.$initial.'/'.$series->id.'/'.$next.'/','next');
    $this->acquisitionCatalogLink($this->bbs_root.'/opds/authorslist/'.$initial.'/'.$series->id.'/'.$last.'/', 'last');
    # Content
    foreach($entries as $entry) {
      $url2 = $url.$entry['book']->id.'/';
      $this->partialAcquisitionEntry($entry, $protected);
    }
    $this->footer();
    return $this->closeStream($of);
  }

  /**
   * Create an OpenSearch descriptor
   * @param  string $of=NULL    output URI or NULL for string output
   * @param  sringg $fragment   path fragment for search operation
   * @return XML stream         the OpenSearch descriptor
   */
  function searchDescriptor($of=NULL, $fragment) {
    $this->openStream($of);
    $this->xmlw->startDocument('1.0', 'UTF-8');
    $this->xmlw->startElement('OpenSearchDescription');
      $this->xmlw->writeAttribute('xmlns','http://a9.com/-/spec/opensearch/1.1/');
      $this->xmlw->writeElement('ShortName','BicBucStriim');
      $this->xmlw->writeElement('Description',$this->l10n->message('opds_by_search3'));
      $this->xmlw->writeElement('InputEncoding','UTF-8');
      $this->xmlw->writeElement('OutputEncoding','UTF-8');
      $this->xmlw->writeElement('Language',$this->l10n->user_lang);
      // TODO Image Element
      
      // TODO HTML?
      // $this->xmlw->startElement('Url');
      //   $this->xmlw->writeAttribute('type', 'text/html');
      //   $this->xmlw->writeAttribute('template', $this->bbs_root.$fragment.'?search={searchTerms}');
      // $this->xmlw->endElement();          
      $this->xmlw->startElement('Url');
        $this->xmlw->writeAttribute('type', self::ATOM_CATALOG);
        $this->xmlw->writeAttribute('template', $this->bbs_root.$fragment.'?search={searchTerms}');
      $this->xmlw->endElement();    
      $this->xmlw->startElement('Url');
        $this->xmlw->writeAttribute('type', self::OPDS_MIME_CATALOG);
        $this->xmlw->writeAttribute('template', $this->bbs_root.$fragment.'?search={searchTerms}');
      $this->xmlw->endElement();    

      // TODO Tags?
      // $this->xmlw->startElement('Url');
      //   $this->xmlw->writeAttribute('type', 'x-suggestions+xml');
      //   $this->xmlw->writeAttribute('rel', 'suggestions');
      //   $this->xmlw->writeAttribute('template', $this->bbs_root.$fragment.'?search={searchTerms}');
      // $this->xmlw->endElement();    
      // 
      // TODO Tags?
      // $this->xmlw->startElement('Url');
      //   $this->xmlw->writeAttribute('type', 'application/x-suggestions+json');
      //   $this->xmlw->writeAttribute('rel', 'suggestions');
      //   $this->xmlw->writeAttribute('template', $this->bbs_root.$fragment.'?search={searchTerms}');
      // $this->xmlw->endElement();    
      
      $this->xmlw->startElement('Query');
        $this->xmlw->writeAttribute('role', 'example');
        $this->xmlw->writeAttribute('searchTerms', 'example');
      $this->xmlw->endElement();    
    $this->xmlw->endElement(); 
    $this->xmlw->endDocument();   
    return $this->closeStream($of); 
  }

  /**
   * Generate a paginated acquisition catalog for books as search results
   * @param  string   $of=NULL   output URI or NULL for string output
   * @param  array    $entries   an array of Book
   * @param  boolean  $protected true = we need password authentication before a download
   * @param  int      $page      number of page to show, minimum 0
   * @param  int      $next      number of the nextPage to show, or NULL
   * @param  int      $last      number of the last page
   * @param  string   $search    search terms
   * @param  int      $total     total number of search results
   * @param  int      $page_size number of entries per search page
   * @return XML stream          the search result feed
   */
  function searchCatalog($of=NULL, $entries, $protected, $page, $next, $last, $search, $total, $page_size) {
    $this->openStream($of);
    $this->header('opds_by_search1', 
      'opds_by_search2',
      '/opds/searchlist/'.$page.':'.urlencode($search),
      ': '.$search);
    $this->acquisitionCatalogLink($this->bbs_root.'/opds/searchlist/'.$page.'/','self');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'start');
    $this->navigationCatalogLink($this->bbs_root.'/opds/', 'up');
    
    $this->acquisitionCatalogLink($this->bbs_root.'/opds/searchlist/0/','first');
    if ($page > 0)
      $this->acquisitionCatalogLink($this->bbs_root.'/opds/searchlist/'.($page-1).'/','previous');
    if (!is_null($next))
      $this->acquisitionCatalogLink($this->bbs_root.'/opds/searchlist/'.$next.'/','next');
    $this->acquisitionCatalogLink($this->bbs_root.'/opds/searchlist/'.$last.'/', 'last');
    # response elements    
    $this->xmlw->writeElement('opensearch:totalResults', $total);
    $this->xmlw->writeElement('opensearch:startIndex', $page*$page_size);
    $this->xmlw->writeElement('opensearch:itemsPerPage', $page_size);
    # the query
    $this->xmlw->startElement('Query');
        $this->xmlw->writeAttribute('role', 'request');
        $this->xmlw->writeAttribute('searchTerms', $search);
        $this->xmlw->writeAttribute('startPage', 0);
      $this->xmlw->endElement();    
    # Content
    foreach($entries as $entry)
      $this->partialAcquisitionEntry($entry, $protected);
    $this->footer();
    return $this->closeStream($of);
  }

############ Common stuff ############
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
    $this->xmlw->startElement('content');
    $this->xmlw->writeAttribute('type', 'text/html');
    $this->xmlw->text($entry['comment']);
    $this->xmlw->endElement(); 
    $this->xmlw->startElement("dc:language");
    $this->xmlw->text($entry['language']);
    $this->xmlw->endElement();
    if (isset($entry['book']->thumbnail) && $entry['book']->thumbnail) 
      $tlink = $this->bbs_root.'/data/titles/thumb_'.$entry['book']->id.'.png';
    else
      $tlink = $titleLink.'/thumbnail/';
    $this->thumbnailLink($tlink);
    $this->imageLink($titleLink.'/cover/');
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
    foreach($entry['tags'] as $category) {
      $this->xmlw->startElement('category');
      $this->xmlw->writeAttribute('term', $category->name);
      $this->xmlw->writeAttribute('label', $category->name);
      $this->xmlw->endElement();
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
   * @param  string $title_ext optional extionsion for title, not translated
   */
  function header($title, $subtitle,$id, $title_ext='') {
    $this->xmlw->startDocument('1.0', 'UTF-8');
    $this->xmlw->startElement('feed');
    $this->xmlw->writeAttribute('xmlns:dc', 'http://purl.org/dc/terms/'); 
    $this->xmlw->writeAttribute('xmlns:opds', 'http://opds-spec.org/2010/catalog'); 
    $this->xmlw->writeAttribute('xmlns:thr','http://purl.org/syndication/thread/1.0');
    $this->xmlw->writeAttribute('xmlns:opensearch','http://a9.com/-/spec/opensearch/1.1/');
    $this->xmlw->writeAttribute('xml:lang',$this->l10n->user_lang);
    $this->xmlw->writeAttribute('xmlns', 'http://www.w3.org/2005/Atom');    
    $this->xmlw->writeElement('title',$this->l10n->message($title).$title_ext);
    $this->xmlw->writeElement('subtitle',$this->l10n->message($subtitle));
    #$this->xmlw->writeElement('icon',$this->bbs_root.'/favicon.ico');
    $this->xmlw->startElement('author');
    $this->xmlw->writeElement('name', 'BicBucStriim '.$this->bbs_version);
    # TODO: textmulch url for feed uri
    $this->xmlw->writeElement('uri', 'http://rvolz.github.com/BicBucStriim');
    $this->xmlw->endElement();
    #$this->xmlw->writeElement('id', $this->bbs_root.$id);
    $this->xmlw->writeElement('id', 'urn:bbs:calibre:'.$id);
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
    $this->link($href, 'image/jpeg', 'http://opds-spec.org/image');
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
   * Link to the OpenSearch document
   * @return xml    XML link to OpenSearch
   */
  function searchLink() {
    $this->link($this->bbs_root.'/opds/opensearch.xml', 'application/opensearchdescription+xml', 
      'search', $this->l10n->message('opds_by_search3'));
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