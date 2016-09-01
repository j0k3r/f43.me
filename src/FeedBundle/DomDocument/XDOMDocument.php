<?php

namespace Api43\FeedBundle\DomDocument;

/**
 * Custom class to handle bad "unterminated entity references".
 *
 * @see http://www.php.net/manual/fr/domdocument.createelement.php#73617
 */
class XDOMDocument extends \DOMDocument
{
    /**
     * Create a new XDOMDocument.
     *
     * @param string|null $version  The version number of the document as part of the XML declaration
     * @param string|null $encoding The encoding of the document as part of the XML declaration
     */
    public function __construct($version = null, $encoding = null)
    {
        parent::__construct($version, $encoding);

        $this->registerNodeClass('DOMElement', 'Api43\FeedBundle\DomDocument\XDOMElement');
    }

    /**
     * Create a new instance of XDOMElement.
     *
     * @param string      $name         The tag name of the element
     * @param string|null $value        The value of the element
     * @param string|null $namespaceURI The namespace of the element
     *
     * @return XDOMElement
     */
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
