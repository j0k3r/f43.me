<?php

namespace Api43\FeedBundle\Tests\EventListener;

use Api43\FeedBundle\Event\FeedItemEvent;
use Api43\FeedBundle\EventListener\FeedItemSubscriber;

class FeedItemSubscriberTest extends \PHPUnit_Framework_TestCase
{
    protected $feedItemSubscriber;

    public function setUp()
    {
        $router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $this->feedItemSubscriber = new FeedItemSubscriber('http://0.0.0.0', $router);
    }

    public function testOnItemCached()
    {
        $event = new FeedItemEvent(array('bar.unknown'));
        $res = $this->feedItemSubscriber->pingHub($event);

        // the hub url is invalid, so it will be generate an error and return false
        $this->assertFalse($res);
    }
}
