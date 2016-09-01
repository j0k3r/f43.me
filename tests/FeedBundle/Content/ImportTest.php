<?php

namespace Tests\FeedBundle\Content;

use Api43\FeedBundle\Content\Extractor;
use Api43\FeedBundle\Content\Import;
use Api43\FeedBundle\Parser\Internal;
use Api43\FeedBundle\Document\Feed;
use Api43\FeedBundle\Extractor\ExtractorChain;
use Api43\FeedBundle\Improver\ImproverChain;
use Api43\FeedBundle\Parser\ParserChain;
use Api43\FeedBundle\Improver\Reddit;
use Api43\FeedBundle\Extractor\Youtube;
use Graby\Graby;
use Psr\Log\NullLogger;
use GuzzleHttp\Client;

/**
 * Crazy complicated tests with too much mocks to replicate a bug where url with & in reddit feed are converted to &amp; and breaks the link
 */
class ImportTest extends \PHPUnit_Framework_TestCase
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
                                    <span><a href="'.$link.'">[link]</a></span> &#32;
                                </td>
                            </tr>
                        </table>',
                        'attribs' => ['' => ['type' => 'html']],
                        'xml_base' => '',
                        'xml_base_explicit' => false,
                        'xml_lang' => '',
                    ]],
                ]
            ]
        ]);
        $rssFeedItem->set_registry(new \SimplePie_Registry());

        $rssFeed->data = [
            'items' => [
                $rssFeedItem
            ]
        ];

        $rssFeed->init();

        $simplePie = $this->getMockBuilder('Api43\FeedBundle\Xml\SimplePieProxy')
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

        $client = new Client();

        $youtube = new Youtube();
        $youtube->setClient($client);

        $extractorChain = new ExtractorChain();
        $extractorChain->addExtractor($youtube, 'youtube');

        $improverChain = new ImproverChain();
        $improverChain->addImprover(new Reddit($client), 'reddit');

        $parserChain = new ParserChain();
        $parserChain->addParser(new Internal(new Graby()), 'internal');

        $extractor = new Extractor($extractorChain, $improverChain, $parserChain);

        $feedRepo = $this->getMockBuilder('Api43\FeedBundle\Repository\FeedRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $feedRepo->expects($this->once())
            // simulate "findOneByslug"
            ->method('__call')
            ->willReturn($feed);

        $feedItemRepo = $this->getMockBuilder('Api43\FeedBundle\Repository\FeedItemRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $dm = $this->getMockBuilder('Doctrine\ODM\MongoDB\DocumentManager')
            ->setMethods(['getRepository', 'persist', 'flush', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();

        $dm->expects($this->exactly(2))
            ->method('getRepository')
            ->will($this->onConsecutiveCalls(
                $feedRepo,
                $feedItemRepo
            ));

        $import = new Import($simplePie, $extractor, $eventDispatcher, $dm, new NullLogger());
        $res = $import->process([$feed]);

        $this->assertEquals(1, $res);
        $this->assertCount(1, $feed->getFeeditems());
        $this->assertEquals($link, $feed->getFeeditems()[0]->getPermalink());
        $this->assertEquals($link, $feed->getFeeditems()[0]->getLink());
    }

    public function testRedditFeedAndYoutube()
    {
        $link = 'https://www.youtube.com/watch?time_continue=162&v=TeVLxcekEsw';

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
                                    <span><a href="'.$link.'">[link]</a></span> &#32;
                                </td>
                            </tr>
                        </table>',
                        'attribs' => ['' => ['type' => 'html']],
                        'xml_base' => '',
                        'xml_base_explicit' => false,
                        'xml_lang' => '',
                    ]],
                ]
            ]
        ]);
        $rssFeedItem->set_registry(new \SimplePie_Registry());

        $rssFeed->data = [
            'items' => [
                $rssFeedItem
            ]
        ];

        $rssFeed->init();

        $simplePie = $this->getMockBuilder('Api43\FeedBundle\Xml\SimplePieProxy')
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

        $client = new Client();

        $extractorChain = new ExtractorChain();
        // $extractorChain->addExtractor($reddit, 'reddit');

        $improverChain = new ImproverChain();
        $improverChain->addImprover(new Reddit($client), 'reddit');

        $parserChain = new ParserChain();
        $parserChain->addParser(new Internal(new Graby()), 'internal');

        $extractor = new Extractor($extractorChain, $improverChain, $parserChain);

        $feedRepo = $this->getMockBuilder('Api43\FeedBundle\Repository\FeedRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $feedRepo->expects($this->once())
            // simulate "findOneByslug"
            ->method('__call')
            ->willReturn($feed);

        $feedItemRepo = $this->getMockBuilder('Api43\FeedBundle\Repository\FeedItemRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $dm = $this->getMockBuilder('Doctrine\ODM\MongoDB\DocumentManager')
            ->setMethods(['getRepository', 'persist', 'flush', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();

        $dm->expects($this->exactly(2))
            ->method('getRepository')
            ->will($this->onConsecutiveCalls(
                $feedRepo,
                $feedItemRepo
            ));

        $import = new Import($simplePie, $extractor, $eventDispatcher, $dm, new NullLogger());
        $res = $import->process([$feed]);

        $this->assertEquals(1, $res);
        $this->assertCount(1, $feed->getFeeditems());
        $this->assertEquals($link, $feed->getFeeditems()[0]->getPermalink());
        $this->assertEquals($link, $feed->getFeeditems()[0]->getLink());
        $this->assertContains('iframe', $feed->getFeeditems()[0]->getContent());
    }
}
