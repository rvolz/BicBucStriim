<?php


namespace App\Domain\Epub;


use DOMDocument;
use DOMXPath;

class EPubDOMXPath extends DOMXPath {
    public function __construct(DOMDocument $doc){
        parent::__construct($doc);

        if(is_a($doc->documentElement, EPubDOMElement::class)){
            foreach($doc->documentElement->namespaces as $ns => $url){
                $this->registerNamespace($ns,$url);
            }
        }
    }
}
