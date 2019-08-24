<?php

namespace Tests\AppBundle\EventListener;

use AppBundle\Event\ItemsCachedEvent;
use AppBundle\EventListener\ItemSubscriber;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tests\AppBundle\AppTestCase;

class ItemSubscriberTest extends AppTestCase
{
    public function testOnItemCachedNoHubDefined()
    {
        $router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $client = self::getMockClient();

        $itemSubscriber = new ItemSubscriber('', $router, $client);

        $event = new ItemsCachedEvent(['bar.unknown']);
        $res = $itemSubscriber->pingHub($event);

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
            ->willReturn('http://f43.me/rss.xml');

        $client = self::getMockClient([(new Response(500, []))]);

        $itemSubscriber = new ItemSubscriber('http://f43.me', $router, $client);

        $event = new ItemsCachedEvent(['bar.unknown']);
        $res = $itemSubscriber->pingHub($event);

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
            ->willReturn('http://f43.me/rss.xml');

        $client = self::getMockClient([(new Response(204))]);

        $itemSubscriber = new ItemSubscriber('http://f43.me', $router, $client);

        $event = new ItemsCachedEvent(['bar.unknown']);
        $res = $itemSubscriber->pingHub($event);

        $this->assertTrue($res);
    }
}
