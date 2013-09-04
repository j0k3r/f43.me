<?php

namespace j0k3r\FeedBundle\DomDocument;

/**
 * Custom class to handle bad "unterminated entity references"
 *
 * @see http://www.php.net/manual/fr/domdocument.createelement.php#73617
 */
class XDOMElement extends \DOMElement
{
    function __construct($name, $value = null, $namespaceURI = null)
    {
        parent::__construct($name, null, $namespaceURI);
    }
}
