<?php

namespace App\MessageHandler;

use App\Content\Import;
use App\Entity\Feed;
use App\Message\FeedSync;
use App\Repository\FeedRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\RouterInterface;

/**
 * Consumer message to fetch new items for a given feed.
 */
#[AsMessageHandler]
class FetchItemsHandler
{
    public function __construct(private readonly ManagerRegistry $doctrine, private readonly FeedRepository $feedRepository, private readonly Import $contentImport, private readonly RouterInterface $router, private readonly LoggerInterface $logger, private readonly string $domain)
    {
    }

    public function __invoke(FeedSync $message): bool
    {
        $feedId = $message->getFeedId();

        /** @var Feed|null */
        $feed = $this->feedRepository->find($feedId);

        if (null === $feed) {
            $this->logger->error('Can not find feed', ['feed' => $feedId]);

            return false;
        }

        $this->logger->notice('Consume f43.feed_new message', ['feed' => $feed->getSlug()]);

        // define host for generating route
        $context = $this->router->getContext();
        $context->setHost($this->domain);

        /** @var EntityManager */
        $em = $this->doctrine->getManager();

        // in case of the manager is closed following a previous exception
        if (!$em->isOpen()) {
            /** @var EntityManager */
            $em = $this->doctrine->resetManager();

            $this->contentImport->setEntityManager($em);
        }

        $totalCached = $this->contentImport->process([$feed]);

        $this->logger->notice('<comment>' . $totalCached . '</comment> items cached for <info>' . $feed->getSlug() . '</info>');

        return true;
    }
}
