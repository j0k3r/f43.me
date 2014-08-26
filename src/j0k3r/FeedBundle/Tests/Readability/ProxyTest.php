<?php

namespace j0k3r\FeedBundle\Tests\Readability;

use j0k3r\FeedBundle\Readability\Proxy;

class ProxyTest extends \PHPUnit_Framework_TestCase
{
    private $readability;
    private $dom;

    protected function setUp()
    {
        $this->buzz = $this->getMockBuilder('Buzz\Browser')
            ->disableOriginalConstructor()
            ->getMock();

        $client = $this->getMockBuilder('Buzz\Client\Curl')
            ->setMethods(array('getInfo'))
            ->disableOriginalConstructor()
            ->getMock();

        $client->expects($this->any())
            ->method('getInfo')
            ->willReturn('http://url');

        $this->response = $this->getMockBuilder('Buzz\Message\Response')
            ->setMethods(array('getContent', 'getHeader'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->buzz->expects($this->any())
            ->method('get')
            ->willReturn($this->response);

        $this->buzz->expects($this->any())
            ->method('getClient')
            ->willReturn($client);

        $this->feed = $this->getMockBuilder('j0k3r\FeedBundle\Document\Feed')
            ->setMethods(array('getFormatter', 'getHost'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->feed->expects($this->any())
            ->method('getFormatter')
            ->willReturn('atom');

        $this->regexs = array(
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
        );
    }

    protected function tearDown()
    {
        unset($this->buzz);
    }

    public function testWithEmptyContent()
    {
        $proxy = new Proxy($this->buzz, 'xxx', 'http://0.0.0.0/api', false, $this->regexs);
        $proxy->setChosenParser('internal');
        $proxy->setFeed($this->feed);
        $proxy->allowAllParser(true);

        $this->response->expects($this->any())
            ->method('getContent')
            ->willReturn('<html/>');

        $proxy->parseContent('http://0.0.0.0', 'default content');

        $this->assertEquals('default content', $proxy->content);
    }

    public function testWithFalseContent()
    {
        $proxy = new Proxy($this->buzz, 'xxx', 'http://0.0.0.0/api', false, $this->regexs);
        $proxy->setChosenParser('internal');
        $proxy->setFeed($this->feed);
        $proxy->allowAllParser(true);

        $this->response->expects($this->any())
            ->method('getContent')
            ->willReturn(false);

        $proxy->parseContent('http://0.0.0.0/content.html', 'default content');

        $this->assertEquals('http://0.0.0.0/content.html', $proxy->url);
        $this->assertEquals('default content', $proxy->content);
    }

    public function testWithVideoContent()
    {
        $proxy = new Proxy($this->buzz, 'xxx', 'http://0.0.0.0/api', false, $this->regexs);
        $proxy->setChosenParser('internal');
        $proxy->setFeed($this->feed);
        $proxy->allowAllParser(true);

        $this->response->expects($this->any())
            ->method('getContent')
            ->willReturn(false);

        $proxy->parseContent('https://www.youtube.com/watch?v=8b7t5iUV0pQ', 'default content');

        $this->assertEquals('https://www.youtube.com/watch?v=8b7t5iUV0pQ', $proxy->url);
        $this->assertEquals('<iframe src="http://www.youtube.com/embed/8b7t5iUV0pQ" width="560" height="315"></iframe>', $proxy->content);
    }

    public function testWithExceptionFromBuzz()
    {
        $proxy = new Proxy($this->buzz, 'xxx', 'http://0.0.0.0/api', false, $this->regexs);
        $proxy->setChosenParser('internal');
        $proxy->setFeed($this->feed);
        $proxy->allowAllParser(true);

        $this->response->expects($this->any())
            ->method('getContent')
            ->will($this->throwException(new \Exception()));

        $proxy->parseContent('http://foo.bar.nowhere/test.html', 'default content');

        $this->assertEquals('http://foo.bar.nowhere/test.html', $proxy->url);
        $this->assertEquals('default content', $proxy->content);
    }

    public function testWithGzipContent()
    {
        $proxy = new Proxy($this->buzz, 'xxx', 'http://0.0.0.0/api', false, $this->regexs);
        $proxy->setChosenParser('internal');
        $proxy->setFeed($this->feed);
        $proxy->allowAllParser(true);

        $this->response->expects($this->any())
            ->method('getContent')
            ->willReturn(gzencode("<p>Le Lorem Ipsum est simplement du faux texte employé dans la composition et la mise en page avant impression. Le Lorem Ipsum est le faux texte standard de l'imprimerie depuis les années 1500, quand un peintre anonyme assembla ensemble des morceaux de texte pour réaliser un livre spécimen de polices de texte.</p>"));

        $this->response->expects($this->any())
            ->method('getHeader')
            ->will($this->returnCallback(function ($param) {
                switch ($param) {
                    case 'Content-Encoding':
                        return 'gzip';

                    case 'Content-Type':
                        return 'text';
                }
            }));

        $proxy->parseContent('http://foo.bar.nowhere/test.html', 'default content');

        $this->assertEquals('http://url', $proxy->url);
        $this->assertContains('readability', $proxy->content);
    }

    public function testWithImageContent()
    {
        $proxy = new Proxy($this->buzz, 'xxx', 'http://0.0.0.0/api', false, $this->regexs);
        $proxy->setChosenParser('internal');
        $proxy->setFeed($this->feed);
        $proxy->allowAllParser(true);

        $this->response->expects($this->any())
            ->method('getContent')
            ->willReturn('<html/>');

        $this->response->expects($this->any())
            ->method('getHeader')
            ->will($this->returnCallback(function ($param) {
                switch ($param) {
                    case 'Content-Encoding':
                        return 'gzip';

                    case 'Content-Type':
                        return 'image';
                }
            }));

        $proxy->parseContent('http://foo.bar.nowhere/test.html', 'default content');

        $this->assertEquals('http://url', $proxy->url);
        $this->assertContains('<img src="http://foo.bar.nowhere/test.html"', $proxy->content);
    }

    public function testWithCustomParser()
    {
        $proxy = new Proxy($this->buzz, 'xxx', 'http://0.0.0.0/api', false, $this->regexs);
        $proxy->setChosenParser('internal');
        $proxy->setFeed($this->feed);
        $proxy->allowAllParser(true);

        $this->feed->expects($this->any())
            ->method('getHost')
            ->will($this->returnValue('Default'));

        $this->response->expects($this->any())
            ->method('getContent')
            ->willReturn('<html/>');

        $proxy->parseContent('http://0.0.0.0', 'default content');

        $this->assertEquals('default content', $proxy->content);
    }
}
