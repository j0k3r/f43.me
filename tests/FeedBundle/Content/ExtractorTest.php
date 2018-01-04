<?php

namespace Tests\FeedBundle\Content;

use Api43\FeedBundle\Content\Extractor;
use Api43\FeedBundle\Parser\Internal;
use PHPUnit\Framework\TestCase;

class ExtractorTest extends TestCase
{
    public function testWithEmptyContent()
    {
        $contentExtractor = $this->getContentExtrator();

        $this->graby->expects($this->any())
            ->method('fetchContent')
            ->willReturn(['html' => false]);

        $contentExtractor->parseContent('http://0.0.0.0', 'default content');

        $this->assertSame('default content', $contentExtractor->content);
    }

    public function testWithException()
    {
        $contentExtractor = $this->getContentExtrator();

        $this->graby->expects($this->any())
            ->method('fetchContent')
            ->will($this->throwException(new \Exception()));

        $contentExtractor->parseContent('http://foo.bar.nowhere/test.html', 'default content');

        $this->assertSame('http://foo.bar.nowhere/test.html', $contentExtractor->url);
        $this->assertSame('default content', $contentExtractor->content);
    }

    public function testWithCustomParser()
    {
        $contentExtractor = $this->getContentExtrator(true);

        $this->graby->expects($this->any())
            ->method('fetchContent')
            ->willReturn(['html' => false]);

        $contentExtractor->parseContent('http://0.0.0.0', 'default content');

        $this->assertSame('default content', $contentExtractor->content);
    }

    public function testWithCustomExtractor()
    {
        $contentExtractor = $this->getContentExtrator(false, true);

        $this->graby->expects($this->any())
            ->method('fetchContent')
            ->willReturn(['html' => false]);

        $contentExtractor->parseContent('http://0.0.0.0', 'default content');

        $this->assertSame('<html/>', $contentExtractor->content);
    }

    /**
     * @expectedException \InvalidArgumentException
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

        $converterChain = $this->getMockBuilder('Api43\FeedBundle\Converter\ConverterChain')
            ->disableOriginalConstructor()
            ->getMock();

        $contentExtractor = new Extractor($extractorChain, $improverChain, $converterChain, new \Api43\FeedBundle\Parser\ParserChain());
        $contentExtractor->init('oops');
    }

    protected function getContentExtrator($customParser = false, $customExtractor = false)
    {
        $feed = $this->getMockBuilder('Api43\FeedBundle\Document\Feed')
            ->setMethods(['getFormatter', 'getHost'])
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

        $defaultImprover = $this->getMockBuilder('Api43\FeedBundle\Improver\DefaultImprover')
            ->disableOriginalConstructor()
            ->getMock();

        $defaultImprover->expects($this->any())
            ->method('updateContent')
            ->will($this->returnArgument(0));

        $defaultImprover->expects($this->any())
            ->method('updateUrl')
            ->will($this->returnArgument(0));

        $improverChain->expects($this->any())
            ->method('match')
            ->willReturn($defaultImprover);

        $converterChain = $this->getMockBuilder('Api43\FeedBundle\Converter\ConverterChain')
            ->disableOriginalConstructor()
            ->getMock();

        $converterChain->expects($this->any())
            ->method('convert')
            ->will($this->returnArgument(0));

        $this->graby = $this->getMockBuilder('Graby\Graby')
            ->setMethods(['fetchContent'])
            ->disableOriginalConstructor()
            ->getMock();

        $internalParser = new Internal($this->graby);

        $parserChain = $this->getMockBuilder('Api43\FeedBundle\Parser\ParserChain')
            ->disableOriginalConstructor()
            ->getMock();

        $parserChain->expects($this->any())
            ->method('getParser')
            ->willReturn($internalParser);

        $contentExtractor = new Extractor($extractorChain, $improverChain, $converterChain, $parserChain);
        $contentExtractor->init('internal', $feed, true);

        if (true === $customParser) {
            $feed->expects($this->any())
                ->method('getHost')
                ->will($this->returnValue('Default'));
        }

        return $contentExtractor;
    }
}
