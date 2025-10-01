<?php

namespace App\Command;

use App\Entity\Feed;
use App\Repository\FeedRepository;
use App\Repository\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

#[AsCommand(name: 'feed:remove-items', description: 'Fetch items from feed to cache them')]
class RemoveItemsCommand
{
    public function __construct(private readonly FeedRepository $feedRepository, private readonly ItemRepository $itemRepository, private readonly EntityManagerInterface $em)
    {
    }

    public function __invoke(
        OutputInterface $output,
        InputInterface $input,
        #[Option(name: 'max', shortcut: 'm', description: 'Number of items to keep in the feed')] int $max = 100,
        #[Option(name: 'slug', description: 'To work on one particular feed (using its slug)')] string|bool $slug = false,
        #[Option(name: 'with-trace', shortcut: 't', description: 'Display debug')] bool $withTrace = false,
    ): int {
        $store = new FlockStore(sys_get_temp_dir());
        $factory = new LockFactory($store);

        $lock = $factory->createLock('feed:remove-items');

        if (!$lock->acquire()) {
            $output->writeLn('<error>The command is already running in another process.</error>');

            return Command::FAILURE;
        }

        // ask user as it will remove all items from its database
        if (0 >= $max) {
            $helper = new QuestionHelper();
            $question = new ConfirmationQuestion('<question>You will remove ALL items, are your sure?</question>', false);

            if (!$helper->ask($input, $output, $question)) {
                $lock->release();

                $output->writeLn('<comment>You *almost* remove everything from your database, pfiou !</comment> Be sure to define a <comment>max</comment> option greater than 0.');

                return Command::FAILURE;
            }
        }

        // retrieve feed to work on
        if ($slug && \is_string($slug)) {
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

        if ($withTrace) {
            $output->writeLn('<info>Feeds</info>: <comment>' . \count($feeds) . '</comment>');
        }

        $totalRemoved = 0;
        foreach ($feeds as $feed) {
            $items = $this->itemRepository->findOldItemsByFeedId(
                $feed->getId(),
                (int) $max
            );

            // manual remove. I can't find a way to perform a remove + skip in one query, it doesn't work :-/
            $removed = 0;
            foreach ($items as $item) {
                $this->em->remove($item);
                ++$removed;
            }

            $totalRemoved += $removed;

            if ($withTrace) {
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
