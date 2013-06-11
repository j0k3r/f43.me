<?php

namespace j0k3r\FeedBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use j0k3r\FeedBundle\Document\Feed;
use j0k3r\FeedBundle\Document\FeedItem;
use j0k3r\FeedBundle\Document\FeedLog;

class FetchItemsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('feed:fetch-items')
            ->setDescription('Fetch items from feed to cache them')
            ->addArgument('age', InputArgument::REQUIRED, '`old` to fetch old feed or `new` to fetch recent feed with no item')
            ->addArgument('slug', InputArgument::OPTIONAL, 'To fetch item for one particulat feed (using its slug)')
            ->addOption('t', null, InputOption::VALUE_NONE, 'Display debug')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container    = $this->getContainer();
        $dm           = $container->get('doctrine.odm.mongodb.document_manager');
        $feedRepo     = $dm->getRepository('j0k3rFeedBundle:Feed');
        $feedItemRepo = $dm->getRepository('j0k3rFeedBundle:FeedItem');
        $progress     = $this->getHelperSet()->get('progress');

        // retrieve feed to work on
        if ($slug = $input->getArgument('slug')) {
            $feed = $feedRepo->findOneBySlug($slug);
            if (!$feed) {
                return $output->writeLn("<error>Unable to find Feed document:</error> <comment>".$slug."</comment>");
            }
            $feeds = array($feed);
        } else {
            if (!in_array($input->getArgument('age'), array('new', 'old'))) {
                return $output->writeLn("<error>Age argument must be `old` or `new`:</error> <comment>".$input->getArgument('age')."</comment> given");
            }

            $feedsWithItems = $feedItemRepo->findAllFeedWithItems();

            // retrieve feed that HAVE items
            if ('old' == $input->getArgument('age')) {
                $feeds = $feedRepo->findByIds($feedsWithItems, 'in');
            }

            // retrieve feeds that DOESN'T have items
            if ('new' == $input->getArgument('age')) {
                $feeds = $feedRepo->findByIds($feedsWithItems, 'notIn');
            }
        }

        $output->writeln('<info>Feeds to check</info>: '.count($feeds));

        foreach ($feeds as $feed) {
            $output->writeln('<info>Working on</info>: '.$feed->getName().' (parser: <comment>'.$feed->getParser().'</comment>)');
            $rssFeed = $container
                ->get('simple_pie_proxy')
                ->setUrl($feed->getLink())
                ->init();

            $parser = $container
                ->get('readability_proxy')
                ->setChoosenParser($feed->getParser());

            $itemCached = $feedItemRepo->findLastItemByFeedId($feed->getId());
            $cached     = 0;

            if ($input->getOption('t')) {
                $total = $rssFeed->get_item_quantity();
                $progress->start($output, $total);
            }

            foreach ($rssFeed->get_items() as $item) {
                // if we find the last cached item, we don't need to cache more item
                if (null !== $itemCached && $itemCached->getPermalink() == $item->get_permalink()) {
                    break;
                }

                $parsedContent = $parser->parseContent($item->get_permalink());

                $feedItem = new FeedItem();
                $feedItem->setTitle($item->get_title());
                $feedItem->setLink($parsedContent->url);
                $feedItem->setContent($parsedContent->content);
                $feedItem->setPermalink($item->get_permalink());
                $feedItem->setPublishedAt($item->get_date());
                $feedItem->setFeed($feed);
                $dm->persist($feedItem);

                $cached++;

                if ($input->getOption('t')) {
                    $progress->advance();
                }
            }

            if (0 !== $cached) {
                if ($input->getOption('t')) {
                    $progress->finish();
                }

                $feedLog = new feedLog();
                $feedLog->setItemsNumber($cached);
                $feedLog->setFeed($feed);
                $dm->persist($feedLog);

                $dm->flush();
                $dm->clear();
            }

            $output->writeln('<info>New cached items</info>: '.$cached);
        }
    }
}
