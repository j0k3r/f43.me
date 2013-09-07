<?php

namespace j0k3r\FeedBundle\DomDocument;

use j0k3r\FeedBundle\DomDocument\XDOMElement;

/**
 * Custom class to handle bad "unterminated entity references"
 *
 * @see http://www.php.net/manual/fr/domdocument.createelement.php#73617
 */
class XDOMDocument extends \DOMDocument
{
    public function __construct($version = null, $encoding = null)
    {
        parent::__construct($version, $encoding);

        $this->registerNodeClass('DOMElement', 'j0k3r\FeedBundle\DomDocument\XDOMElement');
    }

    public function createElement($name, $value = null, $namespaceURI = null)
    {
        $element = new XDOMElement($name, $value, $namespaceURI);

        $element = $this->importNode($element);
        if (!empty($value)) {
            $element->appendChild(new \DOMText($value));
        }

        return $element;
    }
}
