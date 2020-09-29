<?php

namespace App\EventListener;

use App\Event\NewFeedEvent;
use PhpAmqpLib\Exception\AMQPExceptionInterface;
use Swarrot\Broker\Message;
use Swarrot\SwarrotBundle\Broker\Publisher;

class FeedSubscriber
{
    /** @var Publisher */
    protected $publisher;

    /**
     * Create a new subscriber.
     *
     * @param Publisher $publisher Used to push a message to RabbitMQ
     */
    public function __construct(Publisher $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * Push the new feed in the queue so new items will be fetched instantly.
     * In case RabbitMQ isn't well configured avoid exception and let the default command fetch new items.
     */
    public function sync(NewFeedEvent $event): bool
    {
        $message = new Message((string) json_encode([
            'feed_id' => $event->getFeed()->getId(),
        ]));

        try {
            $this->publisher->publish(
                'f43.fetch_items.publisher',
                $message
            );

            return true;
        } catch (AMQPExceptionInterface $e) {
            return false;
        }
    }
}
