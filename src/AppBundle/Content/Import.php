<?php

namespace AppBundle\Content;

use AppBundle\AppEvents;
use AppBundle\Entity\Feed;
use AppBundle\Entity\Item;
use AppBundle\Entity\Log;
use AppBundle\Event\ItemsCachedEvent;
use AppBundle\Repository\FeedRepository;
use AppBundle\Repository\ItemRepository;
use AppBundle\Xml\SimplePieProxy;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Import
{
    private $logger;
    private $simplePieProxy;
    private $extractor;
    private $eventDispatcher;
    private $em;
    private $feedRepository;
    private $itemRepository;

    public function __construct(SimplePieProxy $simplePieProxy, Extractor $extractor, EventDispatcherInterface $eventDispatcher, EntityManagerInterface $em, LoggerInterface $logger, FeedRepository $feedRepository, ItemRepository $itemRepository)
    {
        $this->simplePieProxy = $simplePieProxy;
        $this->extractor = $extractor;
        $this->eventDispatcher = $eventDispatcher;
        $this->em = $em;
        $this->feedRepository = $feedRepository;
        $this->itemRepository = $itemRepository;
        $this->logger = $logger;
    }

    public function setEntityManager(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Process feeds in parameter:
     *     - fetch xml feed
     *     - retrieve all links inside it
     *     - extract content
     *     - create a Item with all information
     *     - a Log with all item cached
     *     - if there are new content, dispatch event to ping hub
     *     - finally, update total item counter.
     *
     * @param array $feeds An array for AppBundle\Entity\Feed
     */
    public function process($feeds)
    {
        $totalCached = 0;
        $feedUpdated = [];

        foreach ($feeds as $feed) {
            $this->logger->debug('<info>Working on</info>: ' . $feed->getName() . ' (parser: <comment>' . $feed->getParser() . '</comment>)');

            $rssFeed = $this
                ->simplePieProxy
                ->setUrl($feed->getLink())
                ->init();

            // update feed description, in case it was empty
            if (0 === \strlen($feed->getDescription()) && 0 !== \strlen($rssFeed->get_description())) {
                $feed->setDescription(html_entity_decode($rssFeed->get_description(), ENT_COMPAT, 'UTF-8'));
                $this->em->persist($feed);
                $this->em->flush();
            }

            $parser = $this
                ->extractor
                ->init($feed->getParser(), $feed, true);

            $cachedLinks = $this->itemRepository->getAllLinks($feed->getId());
            $cached = 0;

            $this->logger->debug('<info>Link to check</info>: <comment>' . $rssFeed->get_item_quantity() . '</comment>');

            foreach ($rssFeed->get_items() as $item) {
                $permalink = $item->get_permalink();

                // if an item already exists, we skip it
                // or if the item doesn't have a link, we won't cache it - will be useless
                if (isset($cachedLinks[$permalink]) || null === $permalink) {
                    continue;
                }

                $this->logger->debug('    <info>Parse content for url</info>: <comment>' . $permalink . '</comment>');

                $parsedContent = $parser->parseContent(
                    $permalink,
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
                    $date = time();
                }
                $date = (new \DateTime())->setTimestamp(strtotime($date));

                $feedItem = new Item($feed);
                $feedItem->setTitle(html_entity_decode($item->get_title(), ENT_COMPAT, 'UTF-8'));
                $feedItem->setLink($parsedContent->url);
                $feedItem->setContent($content);
                $feedItem->setPermalink($permalink);
                $feedItem->setPublishedAt($date);
                $this->em->persist($feedItem);

                $feed->getItems()->add($feedItem);

                ++$cached;
            }

            if ($cached) {
                // save the last time items where updated
                $feed->setLastItemCachedAt(new \DateTime());
                $this->em->persist($feed);

                $totalCached += $cached;

                $feedLog = new Log($feed);
                $feedLog->setItemsNumber($cached);

                $this->em->persist($feedLog);

                $feed->getLogs()->add($feedLog);

                // store feed url updated, to ping hub later
                $feedUpdated[] = $feed->getSlug();
            }

            $this->logger->debug('<info>New cached items</info>: ' . $cached);

            $this->em->flush();
        }

        if (!empty($feedUpdated)) {
            $this->logger->debug('<info>Ping hubs...</info>');

            // send an event about new feed updated
            $event = new ItemsCachedEvent($feedUpdated);

            $this->eventDispatcher->dispatch(
                AppEvents::AFTER_ITEM_CACHED,
                $event
            );
        }

        // update nb items for each udpated feed
        foreach ($feedUpdated as $slug) {
            /** @var Feed */
            $feed = $this->feedRepository->findOneBy(['slug' => $slug]);

            $nbItems = $this->itemRepository->countByFeedId($feed->getId());

            $feed->setNbItems($nbItems);
            $this->em->persist($feed);

            $this->logger->debug('<info>' . $feed->getName() . '</info> items updated: <comment>' . $nbItems . '</comment>');
        }

        $this->em->flush();
        $this->em->clear();

        return $totalCached;
    }
}
