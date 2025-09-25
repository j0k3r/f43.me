<?php

namespace App\Tests\Content;

use App\Content\Extractor;
use App\Converter\ConverterChain;
use App\Entity\Feed;
use App\Extractor\ExtractorChain;
use App\Extractor\Twitter;
use App\Improver\DefaultImprover;
use App\Improver\ImproverChain;
use App\Parser\Internal;
use App\Parser\ParserChain;
use Graby\Content;
use Graby\Graby;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExtractorTest extends TestCase
{
    /** @var MockObject */
    private $graby;

    public function testWithEmptyContent(): void
    {
        $contentExtractor = $this->getContentExtrator();

        $this->graby->expects($this->any())
            ->method('fetchContent')
            ->willReturn(new Content(200, '', '', '', '', [], '', '', [], false));

        $contentExtractor->parseContent('http://foo.bar.nowhere', 'default content');

        $this->assertSame('default content', $contentExtractor->content);
    }

    public function testWithException(): void
    {
        $contentExtractor = $this->getContentExtrator();

        $this->graby->expects($this->any())
            ->method('fetchContent')
            ->will($this->throwException(new \Exception()));

        $contentExtractor->parseContent('http://foo.bar.nowhere/test.html', 'default content');

        $this->assertSame('http://foo.bar.nowhere/test.html', $contentExtractor->url);
        $this->assertSame('default content', $contentExtractor->content);
    }

    public function testWithCustomParser(): void
    {
        $contentExtractor = $this->getContentExtrator(true);

        $this->graby->expects($this->any())
            ->method('fetchContent')
            ->willReturn(new Content(200, '', '', '', '', [], '', '', [], false));

        $contentExtractor->parseContent('http://foo.bar.nowhere', 'default content');

        $this->assertSame('default content', $contentExtractor->content);
    }

    public function testWithCustomExtractor(): void
    {
        $contentExtractor = $this->getContentExtrator(false, true);

        $this->graby->expects($this->any())
            ->method('fetchContent')
            ->willReturn(new Content(200, '', '', '', '', [], '', '', [], false));

        $contentExtractor->parseContent('http://foo.bar.nowhere', 'default content');

        $this->assertSame('<html/>', $contentExtractor->content);
    }

    public function testInvalidParser(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The given parser "oops" does not exists.');

        $extractorChain = $this->getMockBuilder(ExtractorChain::class)
            ->disableOriginalConstructor()
            ->getMock();

        $improverChain = $this->getMockBuilder(ImproverChain::class)
            ->disableOriginalConstructor()
            ->getMock();

        $converterChain = $this->getMockBuilder(ConverterChain::class)
            ->disableOriginalConstructor()
            ->getMock();

        $contentExtractor = new Extractor($extractorChain, $improverChain, $converterChain, new ParserChain());
        $contentExtractor->init('oops');
    }

    protected function getContentExtrator(bool $customParser = false, bool $customExtractor = false): Extractor
    {
        $feed = new Feed();
        $feed->setId(66);
        $feed->setSortBy('created_at');
        $feed->setFormatter('atom');
        $feed->setHost('Default');

        $extractorChain = $this->getMockBuilder(ExtractorChain::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extractorChain->expects($this->any())
            ->method('match')
            ->willReturn(false);

        if (true === $customExtractor) {
            $extractorChain = $this->getMockBuilder(ExtractorChain::class)
                ->disableOriginalConstructor()
                ->getMock();

            $extractor = $this->getMockBuilder(Twitter::class)
                ->disableOriginalConstructor()
                ->getMock();

            $extractor->expects($this->any())
                ->method('getContent')
                ->willReturn('<html/>');

            $extractorChain->expects($this->any())
                ->method('match')
                ->willReturn($extractor);
        }

        $improverChain = $this->getMockBuilder(ImproverChain::class)
            ->disableOriginalConstructor()
            ->getMock();

        $defaultImprover = $this->getMockBuilder(DefaultImprover::class)
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

        $converterChain = $this->getMockBuilder(ConverterChain::class)
            ->disableOriginalConstructor()
            ->getMock();

        $converterChain->expects($this->any())
            ->method('convert')
            ->willReturnArgument(0);

        $this->graby = $this->getMockBuilder(Graby::class)
            ->onlyMethods(['fetchContent'])
            ->disableOriginalConstructor()
            ->getMock();

        $internalParser = new Internal($this->graby);

        $parserChain = $this->getMockBuilder(ParserChain::class)
            ->disableOriginalConstructor()
            ->getMock();

        $parserChain->expects($this->any())
            ->method('getParser')
            ->willReturn($internalParser);

        $contentExtractor = new Extractor($extractorChain, $improverChain, $converterChain, $parserChain);
        $contentExtractor->init('internal', $feed, true);

        return $contentExtractor;
    }
}
