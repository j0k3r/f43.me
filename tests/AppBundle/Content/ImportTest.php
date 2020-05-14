<?php

namespace Tests\AppBundle\Content;

use AppBundle\Content\Extractor;
use AppBundle\Content\Import;
use AppBundle\Converter\ConverterChain;
use AppBundle\Entity\Feed;
use AppBundle\Extractor\ExtractorChain;
use AppBundle\Extractor\Youtube;
use AppBundle\Improver\ImproverChain;
use AppBundle\Improver\Reddit;
use AppBundle\Parser\Internal;
use AppBundle\Parser\ParserChain;
use Graby\Graby;
use Psr\Log\NullLogger;
use Tests\AppBundle\AppTestCase;

/**
 * Crazy complicated tests with too much mocks to replicate a bug where url with & in reddit feed are converted to &amp; and breaks the link.
 */
class ImportTest extends AppTestCase
{
    public function testRedditFeed()
    {
        $link = 'http://s3.reutersmedia.net/resources/r/?d=20160803&t=2&i=1148153511&fh=&fw=&ll=&pl=&sq=&r=2016-08-03T115008Z_3349_RIOEC821JEV7M_RTRMADP_0_OLYMPICS-RIO.jpg';

        $feed = new Feed();
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

        $simplePie = $this->getMockBuilder('AppBundle\Xml\SimplePieProxy')
            ->disableOriginalConstructor()
            ->getMock();

        $simplePie->expects($this->once())
            ->method('setUrl')
            ->willReturn($simplePie);

        $simplePie->expects($this->once())
            ->method('init')
            ->willReturn($rssFeed);

        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->setMethods(['dispatch'])
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

        $feedRepo = $this->getMockBuilder('AppBundle\Repository\FeedRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $feedRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($feed);

        $feedItemRepo = $this->getMockBuilder('AppBundle\Repository\ItemRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->setMethods(['persist', 'flush', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();

        $import = new Import($simplePie, $extractor, $eventDispatcher, $em, new NullLogger(), $feedRepo, $feedItemRepo);
        $res = $import->process([$feed]);

        $this->assertSame(1, $res);
        $this->assertCount(1, $feed->getItems());
        $this->assertSame($link, $feed->getItems()[0]->getPermalink());
        $this->assertSame($link, $feed->getItems()[0]->getLink());
    }

    public function testRedditFeedAndYoutube()
    {
        $link = 'https://www.youtube.com/watch?v=iwGFalTRHDA';

        $feed = new Feed();
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

        $simplePie = $this->getMockBuilder('AppBundle\Xml\SimplePieProxy')
            ->disableOriginalConstructor()
            ->getMock();

        $simplePie->expects($this->once())
            ->method('setUrl')
            ->willReturn($simplePie);

        $simplePie->expects($this->once())
            ->method('init')
            ->willReturn($rssFeed);

        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->setMethods(['dispatch'])
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

        $feedRepo = $this->getMockBuilder('AppBundle\Repository\FeedRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $feedRepo->expects($this->once())
            ->method('findOneBy')
            ->willReturn($feed);

        $feedItemRepo = $this->getMockBuilder('AppBundle\Repository\ItemRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->setMethods(['persist', 'flush', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();

        $import = new Import($simplePie, $extractor, $eventDispatcher, $em, new NullLogger(), $feedRepo, $feedItemRepo);
        $res = $import->process([$feed]);

        $this->assertSame(1, $res);
        $this->assertCount(1, $feed->getItems());
        $this->assertSame($link, $feed->getItems()[0]->getPermalink());
        $this->assertSame($link, $feed->getItems()[0]->getLink());
        $this->assertStringContainsString('iframe', $feed->getItems()[0]->getContent());
    }
}
