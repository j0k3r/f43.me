<?php

namespace AppBundle\Command;

use AppBundle\Content\Import;
use AppBundle\Entity\Feed;
use AppBundle\Repository\FeedRepository;
use AppBundle\Repository\ItemRepository;
use Swarrot\Broker\Message;
use Swarrot\SwarrotBundle\Broker\Publisher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;
use Symfony\Component\Routing\RouterInterface;

class FetchItemsCommand extends Command
{
    private $feedRepository;
    private $itemRepository;
    private $contentImport;
    private $router;
    private $domain;
    private $publisher;

    public function __construct(FeedRepository $feedRepository, ItemRepository $itemRepository, Import $contentImport, RouterInterface $router, $domain, Publisher $publisher)
    {
        $this->feedRepository = $feedRepository;
        $this->itemRepository = $itemRepository;
        $this->contentImport = $contentImport;
        $this->router = $router;
        $this->domain = $domain;
        $this->publisher = $publisher;

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
        $lock = new LockHandler($this->getName());

        if (!$lock->lock()) {
            $output->writeLn('<error>The command is already running in another process.</error>');

            return 0;
        }

        $feeds = [];

        // define host for generating route
        $context = $this->router->getContext();
        $context->setHost($this->domain);

        // retrieve feed to work on
        if ($slug = (string) $input->getOption('slug')) {
            $feed = $this->feedRepository->findOneBy(['slug' => $slug]);
            if (!$feed instanceof Feed) {
                return $output->writeLn('<error>Unable to find Feed document:</error> <comment>' . $slug . '</comment>');
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
            return $output->writeLn('<error>You must add some options to the task :</error> an <comment>age</comment> or a <comment>slug</comment>');
        }

        if ($output->isVerbose()) {
            $output->writeln('<info>Feeds to check</info>: ' . \count($feeds));
        }

        if ($input->getOption('use_queue')) {
            foreach ($feeds as $feed) {
                $message = new Message(json_encode([
                    'feed_id' => $feed->getId(),
                ]));

                $this->publisher->publish(
                    'f43.fetch_items.publisher',
                    $message
                );
            }

            $output->writeLn('<comment>' . \count($feeds) . '</comment> feeds queued.');
        } else {
            // let's import some stuff !
            $totalCached = $this->contentImport->process($feeds);

            $output->writeLn('<comment>' . $totalCached . '</comment> items cached.');
        }
    }
}
