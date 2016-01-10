<?php

namespace Api43\FeedBundle\Xml;

use Api43\FeedBundle\Document\Feed;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Render
{
    protected $generator;
    protected $dm;
    protected $router;

    /**
     * @param string          $generator Like "Generated by foobar"
     * @param DocumentManager $dm
     * @param Router          $router
     */
    public function __construct($generator, DocumentManager $dm, Router $router)
    {
        $this->generator = $generator;
        $this->dm = $dm;
        $this->router = $router;
    }

    /**
     * Render the feed in specified format.
     *
     * @param Feed $feed Feed to render
     *
     * @throws \InvalidArgumentException if given format formatter does not exists
     *
     * @return string
     */
    public function doRender(Feed $feed)
    {
        $items = $this->dm->getRepository('Api43FeedBundle:FeedItem')->findByFeed(
            $feed->getId(),
            $feed->getSortBy()
        );

        $feedUrl = $this->router->generate(
            'feed_xml',
            ['slug' => $feed->getSlug()],
            UrlGeneratorInterface::ABSOLUTE_URL
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
                    sprintf("Format '%s' is not available. Please see documentation.", $feed->getFormatter())
                );
        }

        return $formatter->render();
    }
}
