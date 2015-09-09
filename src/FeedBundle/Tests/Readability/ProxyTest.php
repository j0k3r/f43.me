<?php

namespace Api43\FeedBundle\Tests\Readability;

use Api43\FeedBundle\Readability\Proxy;
use Api43\FeedBundle\Parser\Internal;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class ProxyTest extends \PHPUnit_Framework_TestCase
{
    protected function getProxy(Response $response, $customParser = false, $customExtractor = false)
    {
        $feed = $this->getMockBuilder('Api43\FeedBundle\Document\Feed')
            ->setMethods(array('getFormatter', 'getHost'))
            ->disableOriginalConstructor()
            ->getMock();

        $feed->expects($this->any())
            ->method('getFormatter')
            ->willReturn('atom');

        $extractorChain = $this->getMockBuilder('Api43\FeedBundle\Extractor\ExtractorChain')
            ->disableOriginalConstructor()
            ->getMock();

        $extractorChain->expects($this->any())
            ->method('match')
            ->willReturn(false);

        if (true === $customExtractor) {
            $extractorChain = $this->getMockBuilder('Api43\FeedBundle\Extractor\ExtractorChain')
                ->disableOriginalConstructor()
                ->getMock();

            $extractor = $this->getMockBuilder('Api43\FeedBundle\Extractor\Twitter')
                ->disableOriginalConstructor()
                ->getMock();

            $extractor->expects($this->any())
                ->method('getContent')
                ->willReturn('<html/>');

            $extractorChain->expects($this->any())
                ->method('match')
                ->willReturn($extractor);
        }

        $improverChain = $this->getMockBuilder('Api43\FeedBundle\Improver\ImproverChain')
            ->disableOriginalConstructor()
            ->getMock();

        $nothingImprover = $this->getMockBuilder('Api43\FeedBundle\Improver\Nothing')
            ->disableOriginalConstructor()
            ->getMock();

        $nothingImprover->expects($this->any())
            ->method('updateContent')
            ->will($this->returnArgument(0));

        $nothingImprover->expects($this->any())
            ->method('updateUrl')
            ->will($this->returnArgument(0));

        $improverChain->expects($this->any())
            ->method('match')
            ->willReturn($nothingImprover);

        $client = new Client();

        $mock = new Mock([$response]);

        $client->getEmitter()->attach($mock);

        $internalParser = new Internal($client, array(
            'unlikelyCandidates' => '/combx|comment|community|disqus|extra|foot|header|menu|remark|rss|shoutbox|sidebar|sponsor|ad-break|agegate|pagination|pager|popup|addthis|response|slate_associated_bn|reseaux|sharing|auteur|tag|feedback|meta|kudo|sidebar|copyright|bio|moreInfo|legal|share/i',
            'okMaybeItsACandidate' => '/and|article|body|column|main|shadow/i',
            'positive' => '/article|body|content|entry|hentry|main|page|attachment|pagination|post|text|blog|story/i',
            'negative' => '/combx|comment|com-|contact|foot|footer|_nav|footnote|masthead|media|meta|outbrain|promo|related|scroll|shoutbox|sidebar|sponsor|shopping|tags|tool|widget|header|aside/i',
            'divToPElements' => '/<(a|blockquote|dl|div|img|ol|p|pre|table|ul)/i',
            'replaceBrs' => '/(<br[^>]*>[ \n\r\t]*){2,}/i',
            'replaceFonts' => '/<(\/?)font[^>]*>/i',
            'normalize' => '/\s{2,}/',
            'killBreaks' => '/(<br\s*\/?>(\s|&nbsp;?)*){1,}/',
            'video' => '!//(player\.|www\.)?(youtube|vimeo|viddler|dailymotion)\.com!i',
            'skipFootnoteLink' => '/^\s*(\[?[a-z0-9]{1,2}\]?|^|edit|citation needed)\s*$/i',
            'attrToRemove' => 'onclick|rel|class|target|fs:definition|alt|id|onload|name|onchange',
            'tagToRemove' => 'select|form|header|footer|aside',
        ));

        $parserChain = $this->getMockBuilder('Api43\FeedBundle\Parser\ParserChain')
            ->disableOriginalConstructor()
            ->getMock();

        $parserChain->expects($this->any())
            ->method('getParser')
            ->willReturn($internalParser);

        $proxy = new Proxy($extractorChain, $improverChain, $parserChain);
        $proxy->init('internal', $feed, true);

        if (true === $customParser) {
            $feed->expects($this->any())
                ->method('getHost')
                ->will($this->returnValue('Default'));
        }

        return $proxy;
    }

    public function testWithEmptyContent()
    {
        $proxy = $this->getProxy(new Response(200, []));

        $proxy->parseContent('http://0.0.0.0', 'default content');

        $this->assertEquals('default content', $proxy->content);
    }

    public function testWithFalseContent()
    {
        $proxy = $this->getProxy(new Response(200, ['Content-Type' => 'text']));

        $proxy->parseContent('http://0.0.0.0/content.html', 'default content');

        $this->assertEquals('http://0.0.0.0/content.html', $proxy->url);
        $this->assertEquals('default content', $proxy->content);
    }

    public function testWithVideoContent()
    {
        $proxy = $this->getProxy(new Response(200, ['Content-Type' => 'text']));

        $proxy->parseContent('https://www.youtube.com/watch?v=8b7t5iUV0pQ', 'default content');

        $this->assertEquals('https://www.youtube.com/watch?v=8b7t5iUV0pQ', $proxy->url);
        $this->assertContains('<iframe src="http://www.youtube.com/embed/8b7t5iUV0pQ" width="560" height="315"', $proxy->content);
    }

    public function testWithExceptionFromGuzzle()
    {
        $proxy = $this->getProxy(new Response(400, []));

        $proxy->parseContent('http://foo.bar.nowhere/test.html', 'default content');

        $this->assertEquals('http://foo.bar.nowhere/test.html', $proxy->url);
        $this->assertEquals('default content', $proxy->content);
    }

    public function testWithGzipContent()
    {
        $proxy = $this->getProxy(new Response(200, ['Content-Encoding' => 'gzip', 'Content-Type' => 'text'], Stream::factory(gzencode("<p>Le Lorem Ipsum est simplement du faux texte employé dans la composition et la mise en page avant impression. Le Lorem Ipsum est le faux texte standard de l'imprimerie depuis les années 1500, quand un peintre anonyme assembla ensemble des morceaux de texte pour réaliser un livre spécimen de polices de texte.</p>"))));

        $proxy->parseContent('http://foo.bar.nowhere/test.html', 'default content');

        $this->assertEquals('http://foo.bar.nowhere/test.html', $proxy->url);
        $this->assertContains('readability', $proxy->content);
    }

    public function testWithImageContent()
    {
        $proxy = $this->getProxy(new Response(200, ['Content-Type' => 'image']));

        $proxy->parseContent('http://foo.bar.nowhere/test.html', 'default content');

        $this->assertEquals('http://foo.bar.nowhere/test.html', $proxy->url);
        $this->assertContains('<img src="http://foo.bar.nowhere/test.html"', $proxy->content);
    }

    public function testWithCustomParser()
    {
        $proxy = $this->getProxy(new Response(200, []), true);

        $proxy->parseContent('http://0.0.0.0', 'default content');

        $this->assertEquals('default content', $proxy->content);
    }

    public function testWithCustomExtractor()
    {
        $proxy = $this->getProxy(new Response(200, []), false, true);

        $proxy->parseContent('http://0.0.0.0', 'default content');

        $this->assertEquals('<html/>', $proxy->content);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The given parser "oops" does not exists.
     */
    public function testInvalidParser()
    {
        $extractorChain = $this->getMockBuilder('Api43\FeedBundle\Extractor\ExtractorChain')
            ->disableOriginalConstructor()
            ->getMock();

        $improverChain = $this->getMockBuilder('Api43\FeedBundle\Improver\ImproverChain')
            ->disableOriginalConstructor()
            ->getMock();

        $proxy = new Proxy($extractorChain, $improverChain, new \Api43\FeedBundle\Parser\ParserChain());
        $proxy->init('oops');
    }
}
