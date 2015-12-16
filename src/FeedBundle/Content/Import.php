<?php

namespace Api43\FeedBundle\Content;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Api43\FeedBundle\Xml\SimplePieProxy;
use Api43\FeedBundle\Document\FeedItem;
use Api43\FeedBundle\Document\FeedLog;
use Api43\FeedBundle\Event\FeedItemEvent;
use Api43\FeedBundle\Api43FeedEvents;

class Import
{
    private $logger;
    private $simplePieProxy;
    private $extractor;
    private $eventDispatcher;
    private $dm;

    public function __construct(SimplePieProxy $simplePieProxy, Extractor $extractor, EventDispatcherInterface $eventDispatcher, DocumentManager $dm, LoggerInterface $logger)
    {
        $this->simplePieProxy = $simplePieProxy;
        $this->extractor = $extractor;
        $this->eventDispatcher = $eventDispatcher;
        $this->dm = $dm;
        $this->logger = $logger;
    }

    /**
     * Process feeds in parameter:
     *     - fetch xml feed
     *     - retrieve all links inside it
     *     - extract content
     *     - create a FeedItem with all information
     *     - a FeedLog with all item cached
     *     - if there are new content, dispatch event to ping hub
     *     - finally, update total item counter.
     *
     * @param array $feeds An array for Api43\FeedBundle\Document\Feed or an Doctrine\ODM\MongoDB\EagerCursor
     */
    public function process($feeds)
    {
        $totalCached = 0;
        $feedUpdated = array();
        $feedRepo = $this->dm->getRepository('Api43FeedBundle:Feed');
        $feedItemRepo = $this->dm->getRepository('Api43FeedBundle:FeedItem');

        foreach ($feeds as $feed) {
            $this->logger->debug('<info>Working on</info>: '.$feed->getName().' (parser: <comment>'.$feed->getParser().'</comment>)');

            $rssFeed = $this
                ->simplePieProxy
                ->setUrl($feed->getLink())
                ->init();

            // update feed description, in case it was empty
            if (0 === strlen($feed->getDescription()) && 0 !== strlen($rssFeed->get_description())) {
                $feed->setDescription(html_entity_decode($rssFeed->get_description(), ENT_COMPAT, 'UTF-8'));
                $this->dm->persist($feed);
                $this->dm->flush($feed);
            }

            $parser = $this
                ->extractor
                ->init($feed->getParser(), $feed, true);

            $cachedLinks = $feedItemRepo->getAllLinks($feed->getId());
            $cached = 0;

            $this->logger->debug('<info>Link to check</info>: <comment>'.$rssFeed->get_item_quantity().'</comment>');

            foreach ($rssFeed->get_items() as $item) {
                // if an item already exists, we skip it
                // or if the item doesn't have a link, we won't cache it - will be useless
                if (isset($cachedLinks[$item->get_permalink()]) || null === $item->get_permalink()) {
                    continue;
                }

                $this->logger->debug('    <info>Parse content for url</info>: <comment>'.$item->get_permalink().'</comment>');

                $parsedContent = $parser->parseContent(
                    $item->get_permalink(),
                    $item->get_description()
                );

                // if readable content failed, use default one from feed item
                $content = $parsedContent->content;
                if (false === $content) {
                    $content = $item->get_content();
                }

                // if there is no date in the feed, we use the current one
                $date = $item->get_date();
                if (null === $date) {
                    $date = date('j F Y, g:i:s a');
                }

                $feedItem = new FeedItem();
                $feedItem->setTitle(html_entity_decode($item->get_title(), ENT_COMPAT, 'UTF-8'));
                $feedItem->setLink($parsedContent->url);
                $feedItem->setContent($content);
                $feedItem->setPermalink($item->get_permalink());
                $feedItem->setPublishedAt($date);
                $feedItem->setFeed($feed);
                $this->dm->persist($feedItem);

                ++$cached;
            }

            if ($cached) {
                // save the last time items where updated
                $feed->setLastItemCachedAt(date('j F Y, g:i:s a'));
                $this->dm->persist($feed);

                $totalCached += $cached;

                $feedLog = new FeedLog();
                $feedLog->setItemsNumber($cached);
                $feedLog->setFeed($feed);

                $this->dm->persist($feedLog);

                // store feed url updated, to ping hub later
                $feedUpdated[] = $feed->getSlug();
            }

            $this->logger->debug('<info>New cached items</info>: '.$cached);

            $this->dm->flush();
        }

        if (!empty($feedUpdated)) {
            $this->logger->debug('<info>Ping hubs...</info>');

            // send an event about new feed updated
            $event = new FeedItemEvent($feedUpdated);

            $this->eventDispatcher->dispatch(
                Api43FeedEvents::AFTER_ITEM_CACHED,
                $event
            );
        }

        // update nb items for each udpated feed
        foreach ($feedUpdated as $slug) {
            $feed = $feedRepo->findOneByslug($slug);

            $nbItems = $feedItemRepo->countByFeedId($feed->getId());

            $feed->setNbItems($nbItems);
            $this->dm->persist($feed);

            $this->logger->debug('<info>'.$feed->getName().'</info> items updated: <comment>'.$nbItems.'</comment>');
        }

        $this->dm->flush();
        $this->dm->clear();

        return $totalCached;
    }
}
