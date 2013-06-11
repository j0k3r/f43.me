<?php

namespace j0k3r\FeedBundle\Formatter;

/**
 * RSS formatter
 *
 * This class provides an RSS formatter
 */
class RssFormatter extends Formatter
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
                'name'        => 'title',
                'method'      => 'getTitle',
                'cdata'       => true,
            ), array(
                'name'        => 'description',
                'method'      => 'getContent',
                'cdata'       => true,
            ), array(
                'name'        => 'link',
                'method'      => 'getLink'
            ), array(
                'name'        => 'pubDate',
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

        $root = $this->dom->createElement('rss');
        $root->setAttribute('version', '2.0');
        $root = $this->dom->appendChild($root);

        $channel = $this->dom->createElement('channel');
        $channel = $root->appendChild($channel);

        $fields = array(
            'title'       => 'getName',
            'description' => 'getName',
            'link'        => 'getHost'
        );

        foreach ($fields as $field => $method) {
            $element = $this->dom->createElement(
                $field,
                $this->feed->$method()
            );
            $channel->appendChild($element);
        }

        $date = new \DateTime();
        $lastBuildDate = $this->dom->createElement('lastBuildDate', $date->format(\DateTime::RSS));

        $channel->appendChild($lastBuildDate);

        foreach ($this->items as $item) {
            $this->addItem($channel, $item, 'item');
        }
    }
}
