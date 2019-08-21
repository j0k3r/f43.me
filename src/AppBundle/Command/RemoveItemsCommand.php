<?php

namespace AppBundle\Command;

use AppBundle\Entity\Feed;
use AppBundle\Repository\FeedRepository;
use AppBundle\Repository\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    private $itemRepository;
    private $em;

    public function __construct(FeedRepository $feedRepository, ItemRepository $itemRepository, EntityManagerInterface $em)
    {
        $this->feedRepository = $feedRepository;
        $this->itemRepository = $itemRepository;
        $this->em = $em;

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
        if ($slug = (string) $input->getOption('slug')) {
            $feed = $this->feedRepository->findOneBy(['slug' => $slug]);
            if (!$feed instanceof Feed) {
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
            $items = $this->itemRepository->findOldItemsByFeedId(
                $feed->getId(),
                (int) $input->getOption('max')
            );

            // manual remove. I can't find a way to perform a remove + skip in one query, it doesn't work :-/
            $removed = 0;
            foreach ($items as $item) {
                $this->em->remove($item);
                ++$removed;
            }

            $totalRemoved += $removed;

            if ($input->getOption('with-trace')) {
                $output->writeLn('<info>' . $feed->getName() . '</info>: <comment>' . $removed . '</comment> removed.');
            }
        }

        $this->em->flush();
        $this->em->clear();

        $output->writeLn('<comment>' . $totalRemoved . '</comment> items removed.');

        $lock->release();
    }
}
