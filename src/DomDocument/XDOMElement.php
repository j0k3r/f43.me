<?php

namespace App\DomDocument;

/**
 * Custom class to handle bad "unterminated entity references".
 *
 * @see http://www.php.net/manual/fr/domdocument.createelement.php#73617
 */
class XDOMElement extends \DOMElement
{
    /**
     * Create a new instance of XDOMElement.
     *
     * @param string      $name         The tag name of the element
     * @param string|null $value        The value of the element
     * @param string|null $namespaceURI The namespace of the element
     */
    public function __construct(string $name, ?string $value = null, ?string $namespaceURI = null)
    {
        parent::__construct((string) $name, '', (string) $namespaceURI);
    }
}
