<?php

namespace j0k3r\FeedBundle\Services;

use j0k3r\FeedBundle\Document\Feed;
use j0k3r\FeedBundle\Formatter;

class RssRender
{
    protected
        $generator,
        $dm,
        $router
    ;

    public function __construct($generator, \Doctrine\ODM\MongoDB\DocumentManager $dm, $router)
    {
        $this->generator = $generator;
        $this->dm = $dm;
        $this->router = $router;
    }

    /**
     * Render the feed in specified format
     *
     * @param Feed $feed Feed to render
     *
     * @return string
     *
     * @throws \InvalidArgumentException if given format formatter does not exists
     */
    public function render(Feed $feed)
    {
        $items = $this->dm->getRepository('j0k3rFeedBundle:FeedItem')->findByFeedId($feed->getId());
        $feedUrl = $this->router->generate(
            'feedapi_feed',
            array('slug' => $feed->getSlug()),
            true
        );

        switch ($feed->getFormatter()) {
            case 'rss':
                $formatter = new Formatter\RssFormatter($feed, $items, $feedUrl, $this->generator);
                break;

            case 'atom':
                $formatter = new Formatter\AtomFormatter($feed, $items, $feedUrl, $this->generator);
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
