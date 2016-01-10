<?php

namespace Api43\FeedBundle\Tests\EventListener;

use Api43\FeedBundle\Event\FeedItemEvent;
use Api43\FeedBundle\EventListener\FeedItemSubscriber;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Subscriber\Mock;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FeedItemSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testOnItemCachedNoHubDefined()
    {
        $router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $client = new Client();

        $feedItemSubscriber = new FeedItemSubscriber('', $router, $client);

        $event = new FeedItemEvent(['bar.unknown']);
        $res = $feedItemSubscriber->pingHub($event);

        // the hub url is invalid, so it will be generate an error and return false
        $this->assertFalse($res);
    }

    public function testOnItemCachedBadResponse()
    {
        $router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $router->expects($this->once())
            ->method('generate')
            ->with('feed_xml', ['slug' => 'bar.unknown'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->will($this->returnValue('http://f43.me/rss.xml'));

        $client = new Client();

        $mock = new Mock([
            new Response(500, []),
        ]);

        $client->getEmitter()->attach($mock);

        $feedItemSubscriber = new FeedItemSubscriber('http://f43.me', $router, $client);

        $event = new FeedItemEvent(['bar.unknown']);
        $res = $feedItemSubscriber->pingHub($event);

        // the hub url is invalid, so it will be generate an error and return false
        $this->assertFalse($res);
    }

    public function testOnItemCachedGoodResponse()
    {
        $router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $router->expects($this->once())
            ->method('generate')
            ->with('feed_xml', ['slug' => 'bar.unknown'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->will($this->returnValue('http://f43.me/rss.xml'));

        $client = new Client();

        $mock = new Mock([
            new Response(204, []),
        ]);

        $client->getEmitter()->attach($mock);

        $feedItemSubscriber = new FeedItemSubscriber('http://f43.me', $router, $client);

        $event = new FeedItemEvent(['bar.unknown']);
        $res = $feedItemSubscriber->pingHub($event);

        $this->assertTrue($res);
    }
}
