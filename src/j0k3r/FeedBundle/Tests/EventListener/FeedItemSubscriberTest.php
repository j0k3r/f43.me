<?php

namespace j0k3r\FeedBundle\Tests\EventListener;

use j0k3r\FeedBundle\Event\FeedItemEvent;
use j0k3r\FeedBundle\EventListener\FeedItemSubscriber;

class FeedItemSubscriberTest extends \PHPUnit_Framework_TestCase
{
    protected $feedItemSubscriber;

    public function setUp()
    {
        $this->feedItemSubscriber = new FeedItemSubscriber('http://0.0.0.0');
    }

    public function testOnItemCached()
    {
        $event = new FeedItemEvent(array('http://rss.foo.bar.unknown'));
        $res = $this->feedItemSubscriber->pingHub($event);

        // the hub url is invalid, so it will be generate an error and return false
        $this->assertFalse($res);
    }
}
