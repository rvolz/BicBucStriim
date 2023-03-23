<?php

namespace App\Domain\Epub;

use DOMDocument;
use Exception;
use ZipArchive;

/**
 * PHP EPub Meta library
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
class EPub
{
    public DOMDocument $xml; //FIXME change to protected, later
    protected EPubDOMXPath $xpath;
    protected string $file;
    protected $meta;
    protected $namespaces;
    protected string $imagetoadd='';

    /**
     * Constructor
     *
     * @param string $file path to epub file to work on
     * @throws Exception if metadata could not be loaded
     */
    public function __construct(string $file)
    {
        // open file
        $this->file = $file;
        $zip = new ZipArchive();
        if (!@$zip->open($this->file)) {
            throw new Exception('Failed to read epub file');
        }

        // read container data
        $data = $zip->getFromName('META-INF/container.xml');
        if ($data == false) {
            throw new Exception('Failed to access epub container data');
        }
        $xml = new DOMDocument();
        $xml->registerNodeClass('DOMElement', EPubDOMElement::class);
        $xml->loadXML($data);
        $xpath = new EPubDOMXPath($xml);
        $nodes = $xpath->query('//n:rootfiles/n:rootfile[@media-type="application/oebps-package+xml"]');
        $this->meta = $nodes->item(0)->attr('full-path');

        // load metadata
        $data = $zip->getFromName($this->meta);
        if (!$data) {
            throw new Exception('Failed to access epub metadata');
        }
        $this->xml =  new DOMDocument();
        $this->xml->registerNodeClass('DOMElement', EPubDOMElement::class);
        $this->xml->loadXML($data);
        $this->xml->formatOutput = true;
        $this->xpath = new EPubDOMXPath($this->xml);

        $zip->close();
    }

    /**
     * file name getter
     */
    public function file()
    {
        return $this->file;
    }

    /**
     * Writes back all meta data changes
     */
    public function save()
    {
        $zip = new ZipArchive();
        $res = @$zip->open($this->file, ZipArchive::CREATE);
        if ($res === false) {
            throw new Exception('Failed to write back metadata');
        }
        $zip->addFromString($this->meta, $this->xml->saveXML());
        // add the cover image
        if ($this->imagetoadd) {
            $path = dirname('/'.$this->meta).'/php-epub-meta-cover.img'; // image path is relative to meta file
            $path = ltrim($path, '/');

            $zip->addFromString($path, file_get_contents($this->imagetoadd));
            $this->imagetoadd='';
        }
        $zip->close();
    }

    /**
     * Get or set the book author(s)
     *
     * Authors should be given with a "file-as" and a real name. The file as
     * is used for sorting in e-readers.
     *
     * Example:
     *
     * array(
     *      'Pratchett, Terry'   => 'Terry Pratchett',
     *      'Simpson, Jacqeline' => 'Jacqueline Simpson',
     * )
     *
     * @params array $authors
     */
    public function Authors($authors=false)
    {
        // set new data
        if ($authors !== false) {
            // Author where given as a comma separated list
            if (is_string($authors)) {
                if ($authors == '') {
                    $authors = [];
                } else {
                    $authors = explode(',', $authors);
                    $authors = array_map('trim', $authors);
                }
            }

            // delete existing nodes
            $nodes = $this->xpath->query('//opf:metadata/dc:creator[@opf:role="aut"]');
            foreach ($nodes as $node) {
                $node->delete();
            }

            // add new nodes
            $parent = $this->xpath->query('//opf:metadata')->item(0);
            foreach ($authors as $as => $name) {
                if (is_int($as)) {
                    $as = $name;
                } //numeric array given
                $node = $parent->newChild('dc:creator', $name);
                $node->attr('opf:role', 'aut');
                $node->attr('opf:file-as', $as);
            }

            $this->reparse();
        }

        // read current data
        $rolefix = false;
        $authors = [];
        $nodes = $this->xpath->query('//opf:metadata/dc:creator[@opf:role="aut"]');
        if ($nodes->length == 0) {
            // no nodes where found, let's try again without role
            $nodes = $this->xpath->query('//opf:metadata/dc:creator');
            $rolefix = true;
        }
        foreach ($nodes as $node) {
            $name = $node->nodeValue;
            $as   = $node->attr('opf:file-as');
            if (!$as) {
                $as = $name;
                $node->attr('opf:file-as', $as);
            }
            if ($rolefix) {
                $node->attr('opf:role', 'aut');
            }
            $authors[$as] = $name;
        }
        return $authors;
    }

    /**
     * Set or get the book title
     *
     * @param string|false $title
     */
    public function Title($title=false)
    {
        return $this->getset('dc:title', $title);
    }

    /**
     * Set or get the book's language
     *
     * @param string|false $lang
     */
    public function Language($lang=false)
    {
        return $this->getset('dc:language', $lang);
    }

    /**
     * Set or get the book' publisher info
     *
     * @param string|false $publisher
     */
    public function Publisher($publisher=false)
    {
        return $this->getset('dc:publisher', $publisher);
    }

    /**
     * Set or get the book's copyright info
     *
     * @param string|false $rights
     */
    public function Copyright($rights=false)
    {
        return $this->getset('dc:rights', $rights);
    }

    /**
     * Set or get the book's description
     *
     * @param string|false $description
     */
    public function Description($description=false): string
    {
        return $this->getset('dc:description', $description);
    }

    /**
     * Set or get the book's ISBN number
     *
     * @param string|false $isbn
     */
    public function ISBN($isbn=false): string
    {
        return $this->getset('dc:identifier', $isbn, 'opf:scheme', 'ISBN');
    }

    /**
     * Set or get the Google Books ID
     *
     * @param string|false $google
     */
    public function Google($google=false): string
    {
        return $this->getset('dc:identifier', $google, 'opf:scheme', 'GOOGLE');
    }

    /**
     * Set or get the Amazon ID of the book
     *
     * @param string|false $amazon
     */
    public function Amazon($amazon=false): string
    {
        return $this->getset('dc:identifier', $amazon, 'opf:scheme', 'AMAZON');
    }

    /**
     * Set or get the book's subjects (aka. tags)
     *
     * Subject should be given as array, but a comma separated string will also
     * be accepted.
     *
     * @param array|false $subjects
     */
    public function Subjects($subjects=false): array
    {
        // setter
        if ($subjects !== false) {
            if (is_string($subjects)) {
                if ($subjects === '') {
                    $subjects = [];
                } else {
                    $subjects = explode(',', $subjects);
                    $subjects = array_map('trim', $subjects);
                }
            }

            // delete previous
            $nodes = $this->xpath->query('//opf:metadata/dc:subject');
            foreach ($nodes as $node) {
                $node->delete();
            }
            // add new ones
            $parent = $this->xpath->query('//opf:metadata')->item(0);
            foreach ($subjects as $subj) {
                $node = $this->xml->createElement('dc:subject', htmlspecialchars($subj));
                $node = $parent->appendChild($node);
            }

            $this->reparse();
        }

        //getter
        $subjects = [];
        $nodes = $this->xpath->query('//opf:metadata/dc:subject');
        foreach ($nodes as $node) {
            $subjects[] =  $node->nodeValue;
        }
        return $subjects;
    }

    /**
     * Read the cover data
     *
     * Returns an associative array with the following keys:
     *
     *   mime  - filetype (usually image/jpeg)
     *   data  - the binary image data
     *   found - the internal path, or false if no image is set in epub
     *
     * When no image is set in the epub file, the binary data for a transparent
     * GIF pixel is returned.
     *
     * When adding a new image this function return no or old data because the
     * image contents are not in the epub file, yet. The image will be added when
     * the save() method is called.
     *
     * @param  string|false $path local filesystem path to a new cover image
     * @param  string|false $mime mime type of the given file
     * @return array
     */
    public function Cover($path=false, $mime=false)
    {
        // set cover
        if ($path !== false) {
            // remove current pointer
            $nodes = $this->xpath->query('//opf:metadata/opf:meta[@name="cover"]');
            foreach ($nodes as $node) {
                $node->delete();
            }
            // remove previous manifest entries if they where made by us
            $nodes = $this->xpath->query('//opf:manifest/opf:item[@id="php-epub-meta-cover"]');
            foreach ($nodes as $node) {
                $node->delete();
            }

            if ($path) {
                // add pointer
                $parent = $this->xpath->query('//opf:metadata')->item(0);
                $node = $parent->newChild('opf:meta');
                $node->attr('opf:name', 'cover');
                $node->attr('opf:content', 'php-epub-meta-cover');

                // add manifest
                $parent = $this->xpath->query('//opf:manifest')->item(0);
                $node = $parent->newChild('opf:item');
                $node->attr('id', 'php-epub-meta-cover');
                $node->attr('opf:href', 'php-epub-meta-cover.img');
                $node->attr('opf:media-type', $mime);

                // remember path for save action
                $this->imagetoadd = $path;
            }

            $this->reparse();
        }

        // load cover
        $nodes = $this->xpath->query('//opf:metadata/opf:meta[@name="cover"]');
        if (!$nodes->length) {
            return $this->no_cover();
        }
        $coverid = (string) $nodes->item(0)->attr('opf:content');
        if (!$coverid) {
            return $this->no_cover();
        }

        $nodes = $this->xpath->query('//opf:manifest/opf:item[@id="'.$coverid.'"]');
        if (!$nodes->length) {
            return $this->no_cover();
        }
        $mime = $nodes->item(0)->attr('opf:media-type');
        $path = $nodes->item(0)->attr('opf:href');
        $path = dirname('/'.$this->meta).'/'.$path; // image path is relative to meta file
        $path = ltrim($path, '/');

        $zip = new ZipArchive();
        if (!@$zip->open($this->file)) {
            throw new Exception('Failed to read epub file');
        }
        $data = $zip->getFromName($path);

        return [
            'mime'  => $mime,
            'data'  => $data,
            'found' => $path,
        ];
    }

    /**
     * A simple getter/setter for simple meta attributes
     *
     * It should only be used for attributes that are expected to be unique
     *
     * @param string $item   XML node to set/get
     * @param string|false $value  New node value
     * @param string|false $att    Attribute name
     * @param string|false $aval   Attribute value
     */
    protected function getset($item, $value=false, $att=false, $aval=false)
    {
        // construct xpath
        $xpath = '//opf:metadata/'.$item;
        if ($att) {
            $xpath .= "[@$att=\"$aval\"]";
        }

        // set value
        if ($value !== false) {
            $value = htmlspecialchars($value);
            $nodes = $this->xpath->query($xpath);
            if ($nodes->length == 1) {
                if ($value === '') {
                    // the user want's to empty this value -> delete the node
                    $nodes->item(0)->delete();
                } else {
                    // replace value
                    $nodes->item(0)->nodeValue = $value;
                }
            } else {
                // if there are multiple matching nodes for some reason delete
                // them. we'll replace them all with our own single one
                foreach ($nodes as $n) {
                    $n->delete();
                }
                // readd them
                if ($value) {
                    $parent = $this->xpath->query('//opf:metadata')->item(0);
                    $node   = $this->xml->createElement($item, $value);
                    $node   = $parent->appendChild($node);
                    //if($att) $node->attr($att,$aval);
                    if ($att) {
                        $node->setAttribute($att, $aval);
                    }
                }
            }

            $this->reparse();
        }

        // get value
        $nodes = $this->xpath->query($xpath);
        if ($nodes->length) {
            return $nodes->item(0)->nodeValue;
        } else {
            return '';
        }
    }

    /**
     * Return a not found response for Cover()
     */
    protected function no_cover()
    {
        return [
            'data'  => base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAEALAAAAAABAAEAAAIBTAA7'),
            'mime'  => 'image/gif',
            'found' => false,
        ];
    }

    /**
     * Reparse the DOM tree
     *
     * I had to rely on this because otherwise xpath failed to find the newly
     * added nodes
     */
    protected function reparse()
    {
        $this->xml->loadXML($this->xml->saveXML());
        $this->xpath = new EPubDOMXPath($this->xml);
    }
}
