<?php

namespace AppBundle\Consumer;

use AppBundle\Content\Import;
use AppBundle\Repository\FeedRepository;
use Psr\Log\LoggerInterface;
use Swarrot\Broker\Message;
use Swarrot\Processor\ProcessorInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
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

    public function __construct(RegistryInterface $doctrine, FeedRepository $feedRepository, Import $contentImport, RouterInterface $router, LoggerInterface $logger, $domain)
    {
        $this->doctrine = $doctrine;
        $this->feedRepository = $feedRepository;
        $this->contentImport = $contentImport;
        $this->router = $router;
        $this->domain = $domain;
        $this->logger = $logger;
    }

    public function process(Message $message, array $options)
    {
        $data = json_decode($message->getBody(), true);

        /** @var \AppBundle\Entity\Feed|null */
        $feed = $this->feedRepository->find($data['feed_id']);

        if (null === $feed) {
            $this->logger->error('Can not find feed', ['feed' => $data['feed_id']]);

            return false;
        }

        $this->logger->notice('Consume f43.feed_new message', ['feed' => $feed->getSlug()]);

        // define host for generating route
        $context = $this->router->getContext();
        $context->setHost($this->domain);

        $em = $this->doctrine->getEntityManager();

        // in case of the manager is closed following a previous exception
        if (!$em->isOpen()) {
            $em = $this->doctrine->resetEntityManager();

            $this->contentImport->setEntityManager($em);
        }

        $totalCached = $this->contentImport->process([$feed]);

        $this->logger->notice('<comment>' . $totalCached . '</comment> items cached for <info>' . $feed->getSlug() . '</info>');

        return true;
    }
}
