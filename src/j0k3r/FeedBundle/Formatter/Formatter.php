<?php

namespace j0k3r\FeedBundle\Formatter;

use j0k3r\FeedBundle\Document\Feed;
use j0k3r\FeedBundle\Document\FeedItem;

/**
 * Formatter
 *
 * This class provides formatter methods
 */
class Formatter
{
    /**
     * @var Feed $feed A feed instance
     */
    protected $feed;

    /**
     * @var Feed $feed A feed instance
     */
    protected $items;

    /**
     * @var DOMDocument $dom XML DOMDocument
     */
    protected $dom;

    /**
     * @var array $fields Contain Field instances for this formatter
     */
    protected $fields = array();

    /**
     * Construct a formatter with given feed
     *
     * @param Feed $feed A feed instance
     */
    public function __construct(Feed $feed, $items)
    {
        $this->feed  = $feed;
        $this->items = $items;
    }

    /**
     * Format field
     *
     * @param array      $field A field instance
     * @param FeedItem   $item  An entity instance
     *
     * @return string
     */
    protected function format($field, FeedItem $item)
    {
        $name   = $field['name'];
        $method = $field['method'];
        $value  = $item->{$method}();

        if (isset($field['cdata'])) {
            $value = $this->dom->createCDATASection($value);

            $element = $this->dom->createElement($name);
            $element->appendChild($value);
        } else if (isset($field['attribute'])) {
            $element = $this->dom->createElement($name);
            $element->setAttribute($field['attribute'], $item->getLink());

        } else {
            if (isset($field['date_format'])) {
                $format = $field['date_format'];
                if (!$value instanceof \DateTime) {
                    throw new \InvalidArgumentException(sprintf('Field "%s" should be a DateTime instance.', $name));
                }

                $value = $value->format($format);
            }

            $element = $this->dom->createElement($name, $value);
        }

        return $element;
    }

    /**
     * This method render the given feed transforming the DOMDocument to XML
     *
     * @return string
     */
    public function render()
    {
        $this->dom->formatOutput = true;

        return $this->dom->saveXml();
    }

    /**
     * Add an entity item to the feed
     *
     * @param \DOMElement   $root The root (feed) DOM element
     * @param FeedItem      $item An entity object
     * @param string        $name Could be "entry", for atom or "item" for rss
     */
    public function addItem(\DOMElement $root, FeedItem $item, $name)
    {
        $node = $this->dom->createElement($name);
        $node = $root->appendChild($node);

        foreach ($this->fields as $field) {
            $element = $this->format($field, $item);
            $node->appendChild($element);
        }
    }
}
