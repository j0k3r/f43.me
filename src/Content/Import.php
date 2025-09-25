<?php

namespace App\Content;

use App\Entity\Feed;
use App\Entity\Item;
use App\Entity\Log;
use App\Event\ItemsCachedEvent;
use App\Repository\FeedRepository;
use App\Repository\ItemRepository;
use App\Xml\SimplePieProxy;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Import
{
    public function __construct(private readonly SimplePieProxy $simplePieProxy, private readonly Extractor $extractor, private readonly EventDispatcherInterface $eventDispatcher, private EntityManagerInterface $em, private readonly LoggerInterface $logger, private readonly FeedRepository $feedRepository, private readonly ItemRepository $itemRepository)
    {
    }

    public function setEntityManager(EntityManagerInterface $em): void
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
     * @param array $feeds An array for App\Entity\Feed
     */
    public function process(array $feeds): int
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
            if ('' === $feed->getDescription() && '' !== (string) $rssFeed->get_description()) {
                $feed->setDescription(html_entity_decode((string) $rssFeed->get_description(), \ENT_COMPAT, 'UTF-8'));
                $this->em->persist($feed);
                $this->em->flush();
            }

            $parser = $this
                ->extractor
                ->init($feed->getParser(), $feed, true);

            $cachedLinks = $this->itemRepository->getAllLinks($feed->getId());
            $cached = 0;

            $this->logger->debug('<info>Link to check</info>: <comment>' . $rssFeed->get_item_quantity() . '</comment>');

            /** @var array<\SimplePie\Item> */
            $items = $rssFeed->get_items();

            foreach ($items as $item) {
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
                if (!$content) {
                    $content = $item->get_content();
                }

                // if there is no date in the feed, we use the current one
                $date = $item->get_date();
                if (null === $date) {
                    $date = time();
                }
                $date = (new \DateTime())->setTimestamp((int) strtotime((string) $date));

                $feedItem = new Item($feed);
                $feedItem->setTitle(html_entity_decode((string) $item->get_title(), \ENT_COMPAT, 'UTF-8'));
                $feedItem->setLink($parsedContent->url);
                $feedItem->setContent((string) $content);
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

            $this->eventDispatcher->dispatch($event);
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
