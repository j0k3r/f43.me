<?php

namespace Api43\FeedBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;

class FetchItemsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('feed:fetch-items')
            ->setDescription('Fetch items from feed to cache them')
            ->addOption('age', null, InputOption::VALUE_OPTIONAL, '`old` to fetch old feed or `new` to fetch recent feed with no item')
            ->addOption('slug', null, InputOption::VALUE_OPTIONAL, 'To fetch item for one particular feed (using its slug)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lock = new LockHandler($this->getName());

        if (!$lock->lock()) {
            $output->writeLn('<error>The command is already running in another process.</error>');

            return 0;
        }

        $feeds = [];
        $container = $this->getContainer();
        $dm = $container->get('doctrine.odm.mongodb.document_manager');

        // define host for generating route
        $context = $container->get('router')->getContext();
        $context->setHost($container->getParameter('domain'));

        $feedRepo = $dm->getRepository('Api43FeedBundle:Feed');
        $feedItemRepo = $dm->getRepository('Api43FeedBundle:FeedItem');

        // retrieve feed to work on
        if ($slug = $input->getOption('slug')) {
            $feed = $feedRepo->findOneBySlug($slug);
            if (!$feed) {
                return $output->writeLn('<error>Unable to find Feed document:</error> <comment>'.$slug.'</comment>');
            }
            $feeds = [$feed];
        } elseif (in_array($input->getOption('age'), ['new', 'old'])) {
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
            return $output->writeLn('<error>You must add some options to the task :</error> an <comment>age</comment> or a <comment>slug</comment>');
        }

        if ($output->isVerbose()) {
            $output->writeln('<info>Feeds to check</info>: '.count($feeds));
        }

        // let's import some stuff !
        $import = $container->get('content_import');
        $totalCached = $import->process($feeds);

        $output->writeLn('<comment>'.$totalCached.'</comment> items cached.');
    }
}
