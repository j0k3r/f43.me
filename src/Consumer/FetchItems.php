<?php

namespace App\Consumer;

use App\Content\Import;
use App\Repository\FeedRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Swarrot\Broker\Message;
use Swarrot\Processor\ProcessorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Consumer message to fetch new items for a given feed.
 */
class FetchItems implements ProcessorInterface
{
    private $doctrine;
    private $feedRepository;
    private $contentImport;
    private $router;
    private $domain;
    private $logger;

    public function __construct(ManagerRegistry $doctrine, FeedRepository $feedRepository, Import $contentImport, RouterInterface $router, LoggerInterface $logger, $domain)
    {
        $this->doctrine = $doctrine;
        $this->feedRepository = $feedRepository;
        $this->contentImport = $contentImport;
        $this->router = $router;
        $this->domain = $domain;
        $this->logger = $logger;
    }

    public function process(Message $message, array $options): bool
    {
        $data = json_decode((string) $message->getBody(), true);

        /** @var \App\Entity\Feed|null */
        $feed = $this->feedRepository->find($data['feed_id']);

        if (null === $feed) {
            $this->logger->error('Can not find feed', ['feed' => $data['feed_id']]);

            return false;
        }

        $this->logger->notice('Consume f43.feed_new message', ['feed' => $feed->getSlug()]);

        // define host for generating route
        $context = $this->router->getContext();
        $context->setHost($this->domain);

        /** @var \Doctrine\ORM\EntityManager */
        $em = $this->doctrine->getManager();

        // in case of the manager is closed following a previous exception
        if (!$em->isOpen()) {
            /** @var \Doctrine\ORM\EntityManager */
            $em = $this->doctrine->resetManager();

            $this->contentImport->setEntityManager($em);
        }

        $totalCached = $this->contentImport->process([$feed]);

        $this->logger->notice('<comment>' . $totalCached . '</comment> items cached for <info>' . $feed->getSlug() . '</info>');

        return true;
    }
}
