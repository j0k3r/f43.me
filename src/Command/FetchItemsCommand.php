<?php

namespace App\Command;

use App\Content\Import;
use App\Entity\Feed;
use App\Repository\FeedRepository;
use App\Repository\ItemRepository;
use Swarrot\Broker\Message;
use Swarrot\SwarrotBundle\Broker\AmqpLibFactory;
use Swarrot\SwarrotBundle\Broker\Publisher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Routing\RouterInterface;

class FetchItemsCommand extends Command
{
    private $feedRepository;
    private $itemRepository;
    private $contentImport;
    private $router;
    private $domain;
    private $publisher;
    private $amqplibFactory;

    public function __construct(FeedRepository $feedRepository, ItemRepository $itemRepository, Import $contentImport = null, RouterInterface $router, Publisher $publisher, $domain, AmqpLibFactory $amqplibFactory = null)
    {
        $this->feedRepository = $feedRepository;
        $this->itemRepository = $itemRepository;
        $this->contentImport = $contentImport;
        $this->router = $router;
        $this->domain = $domain;
        $this->publisher = $publisher;
        $this->amqplibFactory = $amqplibFactory;

        parent::__construct();
    }

    protected function configure()
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('use_queue') && null !== $this->amqplibFactory) {
            // check that queue is empty before pushing new messages
            $message = $this->amqplibFactory
                ->getChannel('rabbitmq')
                ->basic_get('f43.fetch_items');

            if (null !== $message && 0 < $message->delivery_info['message_count']) {
                $output->writeln('Current queue as too much messages (<error>' . $message->delivery_info['message_count'] . '</error>), <comment>skipping</comment>.');

                return 1;
            }
        }

        $store = new FlockStore(sys_get_temp_dir());
        $factory = new LockFactory($store);

        $lock = $factory->createLock((string) $this->getName());

        if (!$lock->acquire()) {
            $output->writeLn('<error>The command is already running in another process.</error>');

            return 1;
        }

        $feeds = [];

        // define host for generating route
        $context = $this->router->getContext();
        $context->setHost($this->domain);

        // retrieve feed to work on
        if ($slug = (string) $input->getOption('slug')) {
            $feed = $this->feedRepository->findOneBy(['slug' => $slug]);
            if (!$feed instanceof Feed) {
                $lock->release();

                $output->writeLn('<error>Unable to find Feed document:</error> <comment>' . $slug . '</comment>');

                return 1;
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

            return 1;
        }

        if ($output->isVerbose()) {
            $output->writeln('<info>Feeds to check</info>: ' . \count($feeds));
        }

        if ($input->getOption('use_queue')) {
            foreach ($feeds as $feed) {
                $message = new Message((string) json_encode([
                    'feed_id' => $feed->getId(),
                ]));

                $this->publisher->publish(
                    'f43.fetch_items.publisher',
                    $message
                );
            }

            $lock->release();

            $output->writeLn('<comment>' . \count($feeds) . '</comment> feeds queued.');

            return 0;
        }

        if (null === $this->contentImport) {
            $lock->release();

            $output->writeLn('<error>contentImport is not defined?</error>');

            return 1;
        }

        // let's import some stuff !
        $totalCached = $this->contentImport->process($feeds);

        $lock->release();

        $output->writeLn('<comment>' . $totalCached . '</comment> items cached.');

        return 0;
    }
}
