<?php

namespace j0k3r\FeedBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveItemsCommand extends BaseFeedCommand
{
    protected function configure()
    {
        $this
            ->setName('feed:remove-items')
            ->setDescription('Fetch items from feed to cache them')
            ->addOption('max', 'm', InputOption::VALUE_OPTIONAL, 'Number of items to keep in the feed', 100)
            ->addOption('slug', null, InputOption::VALUE_OPTIONAL, 'To work on one particulat feed (using its slug)')
            ->addOption('with-trace', 't', InputOption::VALUE_NONE, 'Display debug')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->lockCommand($input->getOptions())) {
            return $output->writeLn("<error>Command locked !</error>");
        }

        // ask user as it will remove all items from its database
        if (0 >= $input->getOption('max')) {
            $dialog = $this->getHelperSet()->get('dialog');
            if (!$dialog->askConfirmation($output, '<question>You will remove ALL items, are your sure?</question>', false)) {
                $this->unlockCommand();

                return $output->writeLn("<comment>You *almost* remove every thing from your database, pfiou !</comment> Be sure to define a <comment>max</comment> option greater than 0.");
            }
        }

        $container    = $this->getContainer();
        $dm           = $container->get('doctrine.odm.mongodb.document_manager');
        $feedRepo     = $dm->getRepository('j0k3rFeedBundle:Feed');
        $feedItemRepo = $dm->getRepository('j0k3rFeedBundle:FeedItem');

        // retrieve feed to work on
        if ($slug = $input->getOption('slug')) {
            $feed = $feedRepo->findOneBySlug($slug);
            if (!$feed) {
                $this->unlockCommand();

                return $output->writeLn("<error>Unable to find Feed document:</error> <comment>".$slug."</comment>");
            }
            $feeds = array($feed);
        } else {
            $feeds = $feedRepo->findAll();
        }

        if ($input->getOption('with-trace')) {
            $output->writeLn('<info>Feeds</info>: <comment>'.count($feeds).'</comment>');
        }

        $totalRemoved = 0;
        foreach ($feeds as $feed) {
            $items = $feedItemRepo->findOldItemsByFeedId(
                $feed->getId(),
                $input->getOption('max')
            );

            // manual remove. I can't find a way to perform a remove + skip in one query, it doesn't work :-/
            $removed = 0;
            foreach ($items as $item) {
                $dm->remove($item);
                ++$removed;
            }

            $totalRemoved += $removed;

            $dm->flush();

            if ($input->getOption('with-trace')) {
                $output->writeLn('<info>'.$feed->getName().'</info>: <comment>'.$removed.'</comment> removed.');
            }
        }

        $output->writeLn('<comment>'.$totalRemoved.'</comment> items removed.');
        $this->unlockCommand();
    }
}
