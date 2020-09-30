<?php

namespace App\EventListener;

use App\Event\NewFeedEvent;
use App\Message\FeedSync;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedSubscriber
{
    /** @var MessageBusInterface */
    protected $bus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    /**
     * Push the new feed in the queue so new items will be fetched instantly.
     */
    public function sync(NewFeedEvent $event): void
    {
        $this->bus->dispatch(new FeedSync($event->getFeed()->getId()));
    }
}
