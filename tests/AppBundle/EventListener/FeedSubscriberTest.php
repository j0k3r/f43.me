<?php

namespace Tests\AppBundle\EventListener;

use AppBundle\Entity\Feed;
use AppBundle\Event\NewFeedEvent;
use AppBundle\EventListener\FeedSubscriber;
use PhpAmqpLib\Exception\AMQPIOException;
use Tests\AppBundle\AppTestCase;

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
