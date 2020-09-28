<?php

namespace App\Tests\EventListener;

use App\Entity\Feed;
use App\Event\NewFeedEvent;
use App\EventListener\FeedSubscriber;
use App\Tests\AppTestCase;
use PhpAmqpLib\Exception\AMQPIOException;

class FeedSubscriberTest extends AppTestCase
{
    public function testOnFeedCreated()
    {
        $publisher = $this->getMockBuilder('Swarrot\SwarrotBundle\Broker\Publisher')
            ->disableOriginalConstructor()
            ->getMock();

        $publisher->expects($this->once())
            ->method('publish');

        $feedSubscriber = new FeedSubscriber($publisher);

        $feed = new Feed();
        $feed->setId(123);

        $event = new NewFeedEvent($feed);

        $res = $feedSubscriber->sync($event);

        $this->assertTrue($res);
    }

    public function testOnFeedCreatedNoRabbitMQ()
    {
        $publisher = $this->getMockBuilder('Swarrot\SwarrotBundle\Broker\Publisher')
            ->disableOriginalConstructor()
            ->getMock();

        $publisher->expects($this->once())
            ->method('publish')
            ->will($this->throwException(new AMQPIOException()));

        $feedSubscriber = new FeedSubscriber($publisher);

        $feed = new Feed();
        $feed->setId(123);

        $event = new NewFeedEvent($feed);

        $res = $feedSubscriber->sync($event);

        $this->assertFalse($res);
    }
}
