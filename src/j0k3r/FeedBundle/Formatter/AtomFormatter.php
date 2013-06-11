<?php

namespace j0k3r\FeedBundle\Formatter;

/**
 * Atom formatter
 *
 * This class provides an Atom formatter
 */
class AtomFormatter extends Formatter
{
    /**
     * Construct a formatter with given feed
     *
     * @param Feed $feed A feed instance
     */
    public function __construct($feed, $items)
    {
        $this->fields = array(
            array(
                'name'        => 'id',
                'method'      => 'getLink',
            ), array(
                'name'        => 'title',
                'method'      => 'getTitle',
                'cdata'       => false,
            ), array(
                'name'        => 'summary',
                'method'      => 'getContent',
                'cdata'       => true,
            ), array(
                'name'        => 'link',
                'method'      => 'getLink',
                'attribute'   => 'href'
            ), array(
                'name'        => 'updated',
                'method'      => 'getPublishedAt',
                'date_format' => \DateTime::RSS,
            ),
        );

        parent::__construct($feed, $items);

        $this->initialize();
    }

    /**
     * Initialize XML DOMDocument nodes and call addItem on all items
     */
    public function initialize()
    {
        $this->dom = new \DOMDocument('1.0', 'utf-8');

        $root = $this->dom->createElement('feed');
        $root->setAttribute('xmlns', 'http://www.w3.org/2005/Atom');
        $root = $this->dom->appendChild($root);

        $identifier = $this->dom->createElement('id', $this->feed->getHost());
        $title      = $this->dom->createElement('title', $this->feed->getName());
        $subtitle   = $this->dom->createElement('subtitle', $this->feed->getName());
        $name       = $this->dom->createElement('name', $this->feed->getName());

        $link = $this->dom->createElement('link');
        $link->setAttribute('href', $this->feed->getHost());

        $date = new \DateTime();
        $updated = $this->dom->createElement('updated', $date->format(\DateTime::ATOM));

        $author = $this->dom->createElement('author');
        $author->appendChild($name);

        $root->appendChild($title);
        $root->appendChild($subtitle);
        $root->appendChild($link);
        $root->appendChild($updated);
        $root->appendChild($identifier);
        $root->appendChild($author);

        foreach ($this->items as $item) {
            $this->addItem($root, $item, 'entry');
        }
    }
}
