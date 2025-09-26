<?php

namespace App\Command;

use App\Content\Import;
use App\Entity\Feed;
use App\Message\FeedSync;
use App\Repository\FeedRepository;
use App\Repository\ItemRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Routing\RouterInterface;

class FetchItemsCommand extends Command
{
    public function __construct(private readonly FeedRepository $feedRepository, private readonly ItemRepository $itemRepository, private readonly ?Import $contentImport, private readonly RouterInterface $router, private readonly string $domain, private readonly TransportInterface $transport, private readonly MessageBusInterface $bus)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('feed:fetch-items')
            ->setDescription('Fetch items from feed to cache them')
            ->addArgument(
                'age',
                InputArgument::OPTIONAL,
                '`old` to fetch old feed or `new` to fetch recent feed with no item',
                'old'
            )
            ->addOption(
                'slug',
                null,
                InputOption::VALUE_OPTIONAL,
                'To fetch item for one particular feed (using its slug)'
            )
            ->addOption(
                'use_queue',
                null,
                InputOption::VALUE_NONE,
                'Push each feed into a queue instead of fetching it right away'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('use_queue') && $this->transport instanceof MessageCountAwareInterface) {
            $count = $this->transport->getMessageCount();

            if (0 < $count) {
                $output->writeln('Current queue as too much messages (<error>' . $count . '</error>), <comment>skipping</comment>.');

                return Command::FAILURE;
            }
        }

        $store = new FlockStore(sys_get_temp_dir());
        $factory = new LockFactory($store);

        $lock = $factory->createLock((string) $this->getName());

        if (!$lock->acquire()) {
            $output->writeLn('<error>The command is already running in another process.</error>');

            return Command::FAILURE;
        }

        $feeds = [];

        // define host for generating route
        $context = $this->router->getContext();
        $context->setHost($this->domain);

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
        } elseif (\in_array($input->getArgument('age'), ['new', 'old'], true)) {
            $feedsWithItems = $this->itemRepository->findAllFeedWithItems();

            // retrieve feed that HAVE items
            if ('old' === $input->getArgument('age')) {
                $feeds = $this->feedRepository->findByIds($feedsWithItems, 'in');
            }

            // retrieve feeds that DOESN'T have items
            if ('new' === $input->getArgument('age')) {
                $feeds = $this->feedRepository->findByIds($feedsWithItems, 'notIn');
            }
        } else {
            $lock->release();

            $output->writeLn('<error>You must add some options to the task :</error> an <comment>age</comment> or a <comment>slug</comment>');

            return Command::FAILURE;
        }

        if ($output->isVerbose()) {
            $output->writeln('<info>Feeds to check</info>: ' . \count($feeds));
        }

        if ($input->getOption('use_queue')) {
            foreach ($feeds as $feed) {
                $this->bus->dispatch(new FeedSync($feed->getId()));
            }

            $lock->release();

            $output->writeLn('<comment>' . \count($feeds) . '</comment> feeds queued.');

            return Command::SUCCESS;
        }

        if (null === $this->contentImport) {
            $lock->release();

            $output->writeLn('<error>contentImport is not defined?</error>');

            return Command::FAILURE;
        }

        // let's import some stuff !
        $totalCached = $this->contentImport->process($feeds);

        $lock->release();

        $output->writeLn('<comment>' . $totalCached . '</comment> items cached.');

        return Command::SUCCESS;
    }
}
