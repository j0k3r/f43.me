<?php

namespace Api43\FeedBundle\Tests\Readability;

use Api43\FeedBundle\Readability\Proxy;
use Api43\FeedBundle\Parser\Internal;

class ProxyTest extends \PHPUnit_Framework_TestCase
{
    protected function getProxy($customParser = false, $customExtractor = false)
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

        $this->graby = $this->getMockBuilder('Graby\Graby')
            ->setMethods(array('fetchContent'))
            ->disableOriginalConstructor()
            ->getMock();

        $internalParser = new Internal($this->graby);

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
        $proxy = $this->getProxy();

        $this->graby->expects($this->any())
            ->method('fetchContent')
            ->willReturn(array('html' => false));

        $proxy->parseContent('http://0.0.0.0', 'default content');

        $this->assertEquals('default content', $proxy->content);
    }

    public function testWithException()
    {
        $proxy = $this->getProxy();

        $this->graby->expects($this->any())
            ->method('fetchContent')
            ->will($this->throwException(new \Exception()));

        $proxy->parseContent('http://foo.bar.nowhere/test.html', 'default content');

        $this->assertEquals('http://foo.bar.nowhere/test.html', $proxy->url);
        $this->assertEquals('default content', $proxy->content);
    }

    public function testWithCustomParser()
    {
        $proxy = $this->getProxy(true);

        $this->graby->expects($this->any())
            ->method('fetchContent')
            ->willReturn(array('html' => false));

        $proxy->parseContent('http://0.0.0.0', 'default content');

        $this->assertEquals('default content', $proxy->content);
    }

    public function testWithCustomExtractor()
    {
        $proxy = $this->getProxy(false, true);

        $this->graby->expects($this->any())
            ->method('fetchContent')
            ->willReturn(array('html' => false));

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
