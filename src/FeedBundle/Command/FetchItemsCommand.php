<?php

namespace Api43\FeedBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;
use Api43\FeedBundle\Document\Feed;
use Api43\FeedBundle\Document\FeedItem;
use Api43\FeedBundle\Document\FeedLog;
use Api43\FeedBundle\Event\FeedItemEvent;
use Api43\FeedBundle\Api43FeedEvents;

class FetchItemsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('feed:fetch-items')
            ->setDescription('Fetch items from feed to cache them')
            ->addOption('age', null, InputOption::VALUE_OPTIONAL, '`old` to fetch old feed or `new` to fetch recent feed with no item')
            ->addOption('slug', null, InputOption::VALUE_OPTIONAL, 'To fetch item for one particular feed (using its slug)')
            ->addOption('with-trace', 't', InputOption::VALUE_NONE, 'Display debug')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lock = new LockHandler($this->getName());

        if (!$lock->lock()) {
            $output->writeLn("<error>The command is already running in another process.</error>");

            return 0;
        }

        $feeds        = array();
        $container    = $this->getContainer();
        $dm           = $container->get('doctrine.odm.mongodb.document_manager');

        // define host for generating route
        $context = $container->get('router')->getContext();
        $context->setHost($container->getParameter('domain'));

        $feedRepo     = $dm->getRepository('Api43FeedBundle:Feed');
        $feedItemRepo = $dm->getRepository('Api43FeedBundle:FeedItem');
        $progress     = $this->getHelperSet()->get('progress');

        // retrieve feed to work on
        if ($slug = $input->getOption('slug')) {
            $feed = $feedRepo->findOneBySlug($slug);
            if (!$feed) {
                return $output->writeLn("<error>Unable to find Feed document:</error> <comment>".$slug."</comment>");
            }
            $feeds = array($feed);
        } elseif (in_array($input->getOption('age'), array('new', 'old'))) {
            $feedsWithItems = $feedItemRepo->findAllFeedWithItems();

            // retrieve feed that HAVE items
            if ('old' == $input->getOption('age')) {
                $feeds = $feedRepo->findByIds($feedsWithItems, 'in');
            }

            // retrieve feeds that DOESN'T have items
            if ('new' == $input->getOption('age')) {
                $feeds = $feedRepo->findByIds($feedsWithItems, 'notIn');
            }
        } else {
            return $output->writeLn("<error>You must add some options to the task :</error> an <comment>age</comment> or a <comment>slug</comment>");
        }

        if ($input->getOption('with-trace')) {
            $output->writeln('<info>Feeds to check</info>: '.count($feeds));
        }

        $totalCached = 0;
        $feedUpdated = array();

        foreach ($feeds as $feed) {
            if ($input->getOption('with-trace')) {
                $output->writeln('<info>Working on</info>: '.$feed->getName().' (parser: <comment>'.$feed->getParser().'</comment>)');
            }

            $rssFeed = $container
                ->get('simple_pie_proxy')
                ->setUrl($feed->getLink())
                ->init();

            // update feed description, in case it was empty
            if (0 === strlen($feed->getDescription()) && 0 !== strlen($rssFeed->get_description())) {
                $feed->setDescription(html_entity_decode($rssFeed->get_description(), ENT_COMPAT, 'UTF-8'));
                $dm->persist($feed);
                $dm->flush($feed);
            }

            $parser = $container
                ->get('readability_proxy')
                ->init($feed->getParser(), $feed, true);

            $cachedLinks = $feedItemRepo->getAllLinks($feed->getId());
            $cached      = 0;

            // show progress bar in trace mode only
            if ($input->getOption('with-trace')) {
                $total = $rssFeed->get_item_quantity();
                $progress->start($output, $total);
            }

            foreach ($rssFeed->get_items() as $item) {
                // if an item already exists, we skip it
                // or if the item doesn't have a link, we won't cache it - will be useless
                if (isset($cachedLinks[$item->get_permalink()]) || null === $item->get_permalink()) {
                    continue;
                }

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
                $dm->persist($feedItem);

                $cached++;

                if ($input->getOption('with-trace')) {
                    $progress->advance();
                }
            }

            if ($cached) {
                if ($input->getOption('with-trace')) {
                    $progress->finish();
                }

                // save the last time items where updated
                $feed->setLastItemCachedAt(date('j F Y, g:i:s a'));
                $dm->persist($feed);

                $totalCached += $cached;

                $feedLog = new FeedLog();
                $feedLog->setItemsNumber($cached);
                $feedLog->setFeed($feed);

                $dm->persist($feedLog);

                // store feed url updated, to ping hub later
                $feedUpdated[] = $feed->getSlug();
            }

            if ($input->getOption('with-trace')) {
                $output->writeln('<info>New cached items</info>: '.$cached);
            }
        }

        $dm->flush();

        if (!empty($feedUpdated)) {
            if ($input->getOption('with-trace')) {
                $output->writeln('<info>Ping hubs...</info>');
            }

            // send an event about new feed updated
            $event = new FeedItemEvent($feedUpdated);

            $container->get('event_dispatcher')->dispatch(
                Api43FeedEvents::AFTER_ITEM_CACHED,
                $event
            );
        }

        $output->writeLn('<comment>'.$totalCached.'</comment> items cached.');

        // update nb items for each udpated feed
        foreach ($feedUpdated as $slug) {
            $feed = $feedRepo->findOneByslug($slug);

            $nbItems = $feedItemRepo->countByFeedId($feed->getId());

            $feed->setNbItems($nbItems);
            $dm->persist($feed);

            if ($input->getOption('with-trace')) {
                $output->writeln('<info>'.$feed->getName().'</info> items updated: <comment>'.$nbItems.'</comment>');
            }
        }

        $dm->flush();
        $dm->clear();
    }
}
