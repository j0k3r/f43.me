<?php

namespace App\Tests\EventListener;

use App\Event\ItemsCachedEvent;
use App\EventListener\ItemSubscriber;
use App\Tests\AppTestCase;
use GuzzleHttp\Psr7\Response;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ItemSubscriberTest extends AppTestCase
{
    public function testOnItemCachedNoHubDefined(): void
    {
        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();

        $client = self::getMockClient();

        $itemSubscriber = new ItemSubscriber('', $router, $client);

        $event = new ItemsCachedEvent(['bar.unknown']);
        $res = $itemSubscriber->pingHub($event);

        // the hub url is invalid, so it will be generate an error and return false
        $this->assertFalse($res);
    }

    public function testOnItemCachedBadResponse(): void
    {
        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();

        $router->expects($this->once())
            ->method('generate')
            ->with('feed_xml', ['slug' => 'bar.unknown'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://f43.me/rss.xml');

        $client = self::getMockClient([new Response(500, [])]);

        $itemSubscriber = new ItemSubscriber('http://f43.me', $router, $client);

        $event = new ItemsCachedEvent(['bar.unknown']);
        $res = $itemSubscriber->pingHub($event);

        // the hub url is invalid, so it will be generate an error and return false
        $this->assertFalse($res);
    }

    public function testOnItemCachedGoodResponse(): void
    {
        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();

        $router->expects($this->once())
            ->method('generate')
            ->with('feed_xml', ['slug' => 'bar.unknown'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://f43.me/rss.xml');

        $client = self::getMockClient([new Response(204)]);

        $itemSubscriber = new ItemSubscriber('http://f43.me', $router, $client);

        $event = new ItemsCachedEvent(['bar.unknown']);
        $res = $itemSubscriber->pingHub($event);

        $this->assertTrue($res);
    }
}
