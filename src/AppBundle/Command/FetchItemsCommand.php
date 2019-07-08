<?php

namespace AppBundle\Command;

use AppBundle\Content\Import;
use AppBundle\Repository\FeedItemRepository;
use AppBundle\Repository\FeedRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;
use Symfony\Component\Routing\RouterInterface;

class FetchItemsCommand extends Command
{
    private $feedRepository;
    private $feedItemRepository;
    private $contentImport;
    private $router;
    private $domain;

    public function __construct(FeedRepository $feedRepository, FeedItemRepository $feedItemRepository, Import $contentImport, RouterInterface $router, $domain)
    {
        $this->feedRepository = $feedRepository;
        $this->feedItemRepository = $feedItemRepository;
        $this->contentImport = $contentImport;
        $this->router = $router;
        $this->domain = $domain;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('feed:fetch-items')
            ->setDescription('Fetch items from feed to cache them')
            ->addOption('age', null, InputOption::VALUE_OPTIONAL, '`old` to fetch old feed or `new` to fetch recent feed with no item')
            ->addOption('slug', null, InputOption::VALUE_OPTIONAL, 'To fetch item for one particular feed (using its slug)')
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
        if ($slug = $input->getOption('slug')) {
            $feed = $this->feedRepository->findOneBy(['slug' => $slug]);
            if (!$feed instanceof \AppBundle\Document\Feed) {
                return $output->writeLn('<error>Unable to find Feed document:</error> <comment>' . $slug . '</comment>');
            }
            $feeds = [$feed];
        } elseif (\in_array($input->getOption('age'), ['new', 'old'], true)) {
            $feedsWithItems = $this->feedItemRepository->findAllFeedWithItems();

            // retrieve feed that HAVE items
            if ('old' === $input->getOption('age')) {
                $feeds = $this->feedRepository->findByIds($feedsWithItems, 'in');
            }

            // retrieve feeds that DOESN'T have items
            if ('new' === $input->getOption('age')) {
                $feeds = $this->feedRepository->findByIds($feedsWithItems, 'notIn');
            }
        } else {
            return $output->writeLn('<error>You must add some options to the task :</error> an <comment>age</comment> or a <comment>slug</comment>');
        }

        if ($output->isVerbose()) {
            $output->writeln('<info>Feeds to check</info>: ' . \count($feeds));
        }

        // let's import some stuff !
        $totalCached = $this->contentImport->process($feeds);

        $output->writeLn('<comment>' . $totalCached . '</comment> items cached.');
    }
}
