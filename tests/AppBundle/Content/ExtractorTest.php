<?php

namespace Tests\AppBundle\Content;

use AppBundle\Content\Extractor;
use AppBundle\Parser\Internal;
use PHPUnit\Framework\TestCase;

class ExtractorTest extends TestCase
{
    private $graby;

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

    public function testInvalidParser()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The given parser "oops" does not exists.');

        $extractorChain = $this->getMockBuilder('AppBundle\Extractor\ExtractorChain')
            ->disableOriginalConstructor()
            ->getMock();

        $improverChain = $this->getMockBuilder('AppBundle\Improver\ImproverChain')
            ->disableOriginalConstructor()
            ->getMock();

        $converterChain = $this->getMockBuilder('AppBundle\Converter\ConverterChain')
            ->disableOriginalConstructor()
            ->getMock();

        $contentExtractor = new Extractor($extractorChain, $improverChain, $converterChain, new \AppBundle\Parser\ParserChain());
        $contentExtractor->init('oops');
    }

    protected function getContentExtrator($customParser = false, $customExtractor = false)
    {
        $feed = $this->getMockBuilder('AppBundle\Entity\Feed')
            ->setMethods(['getFormatter', 'getHost'])
            ->disableOriginalConstructor()
            ->getMock();

        $feed->expects($this->any())
            ->method('getFormatter')
            ->willReturn('atom');

        $extractorChain = $this->getMockBuilder('AppBundle\Extractor\ExtractorChain')
            ->disableOriginalConstructor()
            ->getMock();

        $extractorChain->expects($this->any())
            ->method('match')
            ->willReturn(false);

        if (true === $customExtractor) {
            $extractorChain = $this->getMockBuilder('AppBundle\Extractor\ExtractorChain')
                ->disableOriginalConstructor()
                ->getMock();

            $extractor = $this->getMockBuilder('AppBundle\Extractor\Twitter')
                ->disableOriginalConstructor()
                ->getMock();

            $extractor->expects($this->any())
                ->method('getContent')
                ->willReturn('<html/>');

            $extractorChain->expects($this->any())
                ->method('match')
                ->willReturn($extractor);
        }

        $improverChain = $this->getMockBuilder('AppBundle\Improver\ImproverChain')
            ->disableOriginalConstructor()
            ->getMock();

        $defaultImprover = $this->getMockBuilder('AppBundle\Improver\DefaultImprover')
            ->disableOriginalConstructor()
            ->getMock();

        $defaultImprover->expects($this->any())
            ->method('updateContent')
            ->willReturnArgument(0);

        $defaultImprover->expects($this->any())
            ->method('updateUrl')
            ->willReturnArgument(0);

        $improverChain->expects($this->any())
            ->method('match')
            ->willReturn($defaultImprover);

        $converterChain = $this->getMockBuilder('AppBundle\Converter\ConverterChain')
            ->disableOriginalConstructor()
            ->getMock();

        $converterChain->expects($this->any())
            ->method('convert')
            ->willReturnArgument(0);

        $this->graby = $this->getMockBuilder('Graby\Graby')
            ->setMethods(['fetchContent'])
            ->disableOriginalConstructor()
            ->getMock();

        $internalParser = new Internal($this->graby);

        $parserChain = $this->getMockBuilder('AppBundle\Parser\ParserChain')
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
                ->willReturn('Default');
        }

        return $contentExtractor;
    }
}
