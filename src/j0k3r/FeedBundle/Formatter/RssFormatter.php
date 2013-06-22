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
     * @see parent
     */
    public function setItemFields()
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
    }

    /**
     * @see parent
     */
    public function initialize()
    {
        $this->dom = new \DOMDocument('1.0', 'utf-8');

        $root = $this->dom->createElement('rss');
        $root->setAttribute('version', '2.0');
        $root->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $root = $this->dom->appendChild($root);

        $channel = $this->dom->createElement('channel');
        $channel = $root->appendChild($channel);

        $hub = $this->dom->createElement('atom:link');
        $hub->setAttribute('href', 'http://pubsubhubbub.appspot.com/');
        $hub->setAttribute('rel', 'hub');

        $generator   = $this->dom->createElement('generator', htmlspecialchars($this->generator));
        $title       = $this->dom->createElement('title', htmlspecialchars($this->feed->getName()));
        $description = $this->dom->createElement('description', htmlspecialchars($this->feed->getDescription()));
        $link        = $this->dom->createElement('link', 'http://'.$this->feed->getHost());

        $channel->appendChild($hub);
        $channel->appendChild($title);
        $channel->appendChild($description);
        $channel->appendChild($link);
        $channel->appendChild($generator);

        $date = new \DateTime();
        $lastBuildDate = $this->dom->createElement('lastBuildDate', $date->format(\DateTime::RSS));

        $channel->appendChild($lastBuildDate);

        foreach ($this->items as $item) {
            $this->addItem($channel, $item, 'item');
        }
    }
}
