<?php

namespace App\Tests\Content;

use App\Content\Extractor;
use App\Content\Import;
use App\Converter\ConverterChain;
use App\Entity\Feed;
use App\Entity\Item;
use App\Extractor\ExtractorChain;
use App\Extractor\Youtube;
use App\Improver\ImproverChain;
use App\Improver\Reddit;
use App\Parser\Internal;
use App\Parser\ParserChain;
use App\Tests\AppTestCase;
use Graby\Graby;
use GuzzleHttp\Psr7\Response;
use Psr\Log\NullLogger;

/**
 * Crazy complicated tests with too much mocks to replicate a bug where url with & in reddit feed are converted to &amp; and breaks the link.
 */
class ImportTest extends AppTestCase
{
    public function testRedditFeed(): void
    {
        $link = 'http://s3.reutersmedia.net/resources/r/?d=20160803&t=2&i=1148153511&fh=&fw=&ll=&pl=&sq=&r=2016-08-03T115008Z_3349_RIOEC821JEV7M_RTRMADP_0_OLYMPICS-RIO.jpg';

        $feed = new Feed();
        $feed->setId(66);
        $feed->setParser('internal');
        $feed->setHost('reddit.com');

        $rssFeed = new \SimplePie();
        $rssFeed->order_by_date = false;
        $rssFeed->cache = false;
        $rssFeed->data = [
            'links' => [
                'alternate' => ['https://www.reddit.com/'],
            ],
        ];

        $rssFeedItem = new \SimplePie_Item($rssFeed, [
            'links' => [
                'alternate' => [$link],
            ],
            'title' => 'The default title',
            'enclosures' => '',
            'child' => [
                'http://www.w3.org/2005/Atom' => [
                    'content' => [[
                        'data' => '
                        <table>
                            <tr>
                                <td>
                                    <span><a href="' . $link . '">[link]</a></span> &#32;
                                </td>
                            </tr>
                        </table>',
                        'attribs' => ['' => ['type' => 'html']],
                        'xml_base' => '',
                        'xml_base_explicit' => false,
                        'xml_lang' => '',
                    ]],
                ],
            ],
        ]);
        $rssFeedItem->set_registry(new \SimplePie_Registry());

        $rssFeed->data = [
            'items' => [
                $rssFeedItem,
            ],
        ];

        $rssFeed->init();

        $simplePie = $this->getMockBuilder('App\Xml\SimplePieProxy')
            ->disableOriginalConstructor()
            ->getMock();

        $simplePie->expects($this->once())
            ->method('setUrl')
            ->willReturn($simplePie);

        $simplePie->expects($this->once())
            ->method('init')
            ->willReturn($rssFeed);

        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->onlyMethods(['dispatch'])
            ->disableOriginalConstructor()
            ->getMock();

        $eventDispatcher->expects($this->once())
            ->method('dispatch');

        $client = self::getMockClient();

        $youtube = new Youtube();
        $youtube->setClient($client);
        $youtube->setLogger(new NullLogger());

        $extractorChain = new ExtractorChain();
        $extractorChain->addExtractor($youtube, 'youtube');

        $improverChain = new ImproverChain();
        $improverChain->addImprover(new Reddit($client), 'reddit');

        $parserChain = new ParserChain();
        $parserChain->addParser(new Internal(new Graby()), 'internal');

        $extractor = new Extractor($extractorChain, $improverChain, new ConverterChain(), $parserChain);

        $feedRepo = $this->getMockBuilder('App\Repository\FeedRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $feedRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($feed);

        $feedItemRepo = $this->getMockBuilder('App\Repository\ItemRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->onlyMethods(['persist', 'flush', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();

        $import = new Import($simplePie, $extractor, $eventDispatcher, $em, new NullLogger(), $feedRepo, $feedItemRepo);
        $res = $import->process([$feed]);

        /** @var Item */
        $item = $feed->getItems()[0];

        $this->assertSame(1, $res);
        $this->assertCount(1, $feed->getItems());
        $this->assertSame($link, $item->getPermalink());
        $this->assertSame($link, $item->getLink());
    }

    public function testRedditFeedAndYoutube(): void
    {
        $link = 'https://www.youtube.com/watch?v=iwGFalTRHDA';

        $feed = new Feed();
        $feed->setId(66);
        $feed->setParser('internal');
        $feed->setHost('reddit.com');

        $rssFeed = new \SimplePie();
        $rssFeed->order_by_date = false;
        $rssFeed->cache = false;
        $rssFeed->data = [
            'links' => [
                'alternate' => ['https://www.reddit.com/'],
            ],
        ];

        $rssFeedItem = new \SimplePie_Item($rssFeed, [
            'links' => [
                'alternate' => [$link],
            ],
            'title' => 'The default title',
            'enclosures' => '',
            'child' => [
                'http://www.w3.org/2005/Atom' => [
                    'content' => [[
                        'data' => '
                        <table>
                            <tr>
                                <td>
                                    <span><a href="' . $link . '">[link]</a></span> &#32;
                                </td>
                            </tr>
                        </table>',
                        'attribs' => ['' => ['type' => 'html']],
                        'xml_base' => '',
                        'xml_base_explicit' => false,
                        'xml_lang' => '',
                    ]],
                ],
            ],
        ]);
        $rssFeedItem->set_registry(new \SimplePie_Registry());

        $rssFeed->data = [
            'items' => [
                $rssFeedItem,
            ],
        ];

        $rssFeed->init();

        $simplePie = $this->getMockBuilder('App\Xml\SimplePieProxy')
            ->disableOriginalConstructor()
            ->getMock();

        $simplePie->expects($this->once())
            ->method('setUrl')
            ->willReturn($simplePie);

        $simplePie->expects($this->once())
            ->method('init')
            ->willReturn($rssFeed);

        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->onlyMethods(['dispatch'])
            ->disableOriginalConstructor()
            ->getMock();

        $eventDispatcher->expects($this->once())
            ->method('dispatch');

        $client = self::getMockClient([(new Response(200, ['content-type' => 'application/json'], (string) json_encode([
           'title' => 'Trololo',
           'author_name' => 'KamoKatt',
           'author_url' => 'https://www.youtube.com/user/KamoKatt',
           'type' => 'video',
           'height' => 150,
           'width' => 200,
           'version' => '1.0',
           'provider_name' => 'YouTube',
           'provider_url' => 'https://www.youtube.com/',
           'thumbnail_height' => 360,
           'thumbnail_width' => 480,
           'thumbnail_url' => 'https://i.ytimg.com/vi/iwGFalTRHDA/hqdefault.jpg',
           'html' => '<iframe width="200" height="150" src="https://www.youtube.com/embed/iwGFalTRHDA?feature=oembed" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>',
        ])))]);

        $youtube = new Youtube();
        $youtube->setClient($client);
        $youtube->setLogger(new NullLogger());

        $extractorChain = new ExtractorChain();
        $extractorChain->addExtractor($youtube, 'youtube');

        $improverChain = new ImproverChain();
        $improverChain->addImprover(new Reddit($client), 'reddit');

        $parserChain = new ParserChain();
        $parserChain->addParser(new Internal(new Graby()), 'internal');

        $extractor = new Extractor($extractorChain, $improverChain, new ConverterChain(), $parserChain);

        $feedRepo = $this->getMockBuilder('App\Repository\FeedRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $feedRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($feed);

        $feedItemRepo = $this->getMockBuilder('App\Repository\ItemRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->onlyMethods(['persist', 'flush', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();

        $import = new Import($simplePie, $extractor, $eventDispatcher, $em, new NullLogger(), $feedRepo, $feedItemRepo);
        $res = $import->process([$feed]);

        /** @var Item */
        $item = $feed->getItems()[0];

        $this->assertSame(1, $res);
        $this->assertCount(1, $feed->getItems());
        $this->assertSame($link, $item->getPermalink());
        $this->assertSame($link, $item->getLink());
        $this->assertStringContainsString('iframe', $item->getContent());
    }
}
