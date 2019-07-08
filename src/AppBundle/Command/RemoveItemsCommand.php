<?php

namespace AppBundle\Command;

use AppBundle\Repository\FeedItemRepository;
use AppBundle\Repository\FeedRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\FlockStore;

class RemoveItemsCommand extends Command
{
    private $feedRepository;
    private $feedItemRepository;
    private $dm;

    public function __construct(FeedRepository $feedRepository, FeedItemRepository $feedItemRepository, DocumentManager $dm)
    {
        $this->feedRepository = $feedRepository;
        $this->feedItemRepository = $feedItemRepository;
        $this->dm = $dm;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('feed:remove-items')
            ->setDescription('Fetch items from feed to cache them')
            ->addOption('max', 'm', InputOption::VALUE_OPTIONAL, 'Number of items to keep in the feed', 100)
            ->addOption('slug', null, InputOption::VALUE_OPTIONAL, 'To work on one particular feed (using its slug)')
            ->addOption('with-trace', 't', InputOption::VALUE_NONE, 'Display debug')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $store = new FlockStore(sys_get_temp_dir());
        $factory = new Factory($store);

        $lock = $factory->createLock($this->getName());

        if (!$lock->acquire()) {
            $output->writeLn('<error>The command is already running in another process.</error>');

            return 0;
        }

        // ask user as it will remove all items from its database
        if (0 >= $input->getOption('max')) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('<question>You will remove ALL items, are your sure?</question>', false);

            if (!$helper->ask($input, $output, $question)) {
                return $output->writeLn('<comment>You *almost* remove everything from your database, pfiou !</comment> Be sure to define a <comment>max</comment> option greater than 0.');
            }
        }

        // retrieve feed to work on
        if ($slug = $input->getOption('slug')) {
            $feed = $this->feedRepository->findOneBy(['slug' => $slug]);
            if (!$feed instanceof \AppBundle\Document\Feed) {
                return $output->writeLn('<error>Unable to find Feed document:</error> <comment>' . $slug . '</comment>');
            }
            $feeds = [$feed];
        } else {
            $feeds = $this->feedRepository->findAll();
        }

        if ($input->getOption('with-trace')) {
            $output->writeLn('<info>Feeds</info>: <comment>' . \count($feeds) . '</comment>');
        }

        $totalRemoved = 0;
        foreach ($feeds as $feed) {
            $items = $this->feedItemRepository->findOldItemsByFeedId(
                $feed->getId(),
                $input->getOption('max')
            );

            // manual remove. I can't find a way to perform a remove + skip in one query, it doesn't work :-/
            $removed = 0;
            foreach ($items as $item) {
                $this->dm->remove($item);
                ++$removed;
            }

            $totalRemoved += $removed;

            if ($input->getOption('with-trace')) {
                $output->writeLn('<info>' . $feed->getName() . '</info>: <comment>' . $removed . '</comment> removed.');
            }
        }

        $this->dm->flush();
        $this->dm->clear();

        $output->writeLn('<comment>' . $totalRemoved . '</comment> items removed.');

        $lock->release();
    }
}
