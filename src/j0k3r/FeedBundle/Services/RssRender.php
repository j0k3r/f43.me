<?php

namespace j0k3r\FeedBundle\Services;

use j0k3r\FeedBundle\Document\Feed;
use j0k3r\FeedBundle\Formatter;

class RssRender
{
    protected
        $generator,
        $dm
    ;

    public function __construct($generator, \Doctrine\ODM\MongoDB\DocumentManager $dm)
    {
        $this->generator = $generator;
        $this->dm = $dm;
    }

    /**
     * Render the feed in specified format
     *
     * @param Feed  $feed  Feed to render
     *
     * @return string
     *
     * @throws \InvalidArgumentException if given format formatter does not exists
     */
    public function render(Feed $feed)
    {
        $items = $this->dm->getRepository('j0k3rFeedBundle:FeedItem')->findByFeedId($feed->getId());;

        switch ($feed->getFormatter()) {
            case 'rss':
                $formatter = new Formatter\RssFormatter($feed, $items, $this->generator);
                break;

            case 'atom':
                $formatter = new Formatter\AtomFormatter($feed, $items, $this->generator);
                break;

            default:
                throw new \InvalidArgumentException(
                    sprintf("Format '%s' is not available. Please see documentation.", $format)
                );
                break;
        }

        return $formatter->render();
    }
}
