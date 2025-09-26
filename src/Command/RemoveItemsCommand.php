<?php

namespace App\Command;

use App\Entity\Feed;
use App\Repository\FeedRepository;
use App\Repository\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

class RemoveItemsCommand extends Command
{
    public function __construct(private readonly FeedRepository $feedRepository, private readonly ItemRepository $itemRepository, private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('feed:remove-items')
            ->setDescription('Fetch items from feed to cache them')
            ->addOption('max', 'm', InputOption::VALUE_OPTIONAL, 'Number of items to keep in the feed', '100')
            ->addOption('slug', null, InputOption::VALUE_OPTIONAL, 'To work on one particular feed (using its slug)')
            ->addOption('with-trace', 't', InputOption::VALUE_NONE, 'Display debug')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $store = new FlockStore(sys_get_temp_dir());
        $factory = new LockFactory($store);

        $lock = $factory->createLock((string) $this->getName());

        if (!$lock->acquire()) {
            $output->writeLn('<error>The command is already running in another process.</error>');

            return Command::FAILURE;
        }

        // ask user as it will remove all items from its database
        if (0 >= $input->getOption('max')) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('<question>You will remove ALL items, are your sure?</question>', false);

            if (!$helper->ask($input, $output, $question)) {
                $lock->release();

                $output->writeLn('<comment>You *almost* remove everything from your database, pfiou !</comment> Be sure to define a <comment>max</comment> option greater than 0.');

                return Command::FAILURE;
            }
        }

        // retrieve feed to work on
        $slug = (string) $input->getOption('slug');
        if ($slug) {
            $feed = $this->feedRepository->findOneBy(['slug' => $slug]);
            if (!$feed instanceof Feed) {
                $lock->release();

                $output->writeLn('<error>Unable to find Feed document:</error> <comment>' . $slug . '</comment>');

                return Command::FAILURE;
            }
            $feeds = [$feed];
        } else {
            /** @var Feed[] */
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

        $lock->release();

        $output->writeLn('<comment>' . $totalRemoved . '</comment> items removed.');

        return Command::SUCCESS;
    }
}
