<?php

namespace App\Tests\EventListener;

use App\Entity\Feed;
use App\Event\NewFeedEvent;
use App\EventListener\FeedSubscriber;
use App\Message\FeedSync;
use App\Tests\AppTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class FeedSubscriberTest extends AppTestCase
{
    public function testOnFeedCreated(): void
    {
        $bus = $this->getMockBuilder(MessageBusInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $bus->expects($this->once())
            ->method('dispatch')
            ->willReturn(new Envelope(new FeedSync(123)));

        $feedSubscriber = new FeedSubscriber($bus);

        $feed = new Feed();
        $feed->setId(123);

        $event = new NewFeedEvent($feed);

        $feedSubscriber->sync($event);
    }
}
