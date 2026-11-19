<?php

namespace App\Tests\Content;

use App\Content\Extractor;
use App\Converter\ConverterChain;
use App\Entity\Feed;
use App\Extractor\AbstractExtractor;
use App\Extractor\ExtractorChain;
use App\Improver\DefaultImprover;
use App\Improver\ImproverChain;
use App\Parser\Internal;
use Graby\Content;
use Graby\Graby;
use Graby\HttpClient\EffectiveResponse;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExtractorTest extends TestCase
{
    /** @var MockObject */
    private $graby;

    public function testWithEmptyContent(): void
    {
        $contentExtractor = $this->getContentExtrator();

        $this->graby
            ->method('fetchContent')
            ->willReturn($this->getGrabyContent(''));

        $contentExtractor->parseContent('http://foo.bar.nowhere', 'default content');

        $this->assertSame('default content', $contentExtractor->content);
    }

    public function testWithException(): void
    {
        $contentExtractor = $this->getContentExtrator();

        $this->graby
            ->method('fetchContent')
            ->will($this->throwException(new \Exception()));

        $contentExtractor->parseContent('http://foo.bar.nowhere/test.html', 'default content');

        $this->assertSame('http://foo.bar.nowhere/test.html', $contentExtractor->url);
        $this->assertSame('default content', $contentExtractor->content);
    }

    public function testFailedGrabyIsNotRetried(): void
    {
        $contentExtractor = $this->getContentExtrator();

        $this->graby
            ->expects($this->once())
            ->method('fetchContent')
            ->willReturn($this->getGrabyContent(''));

        $contentExtractor->parseContent('http://foo.bar.nowhere', 'default content');

        $this->assertSame('default content', $contentExtractor->content);
    }

    public function testWithCustomExtractor(): void
    {
        $contentExtractor = $this->getContentExtrator(true);

        $this->graby
            ->method('fetchContent')
            ->willReturn($this->getGrabyContent(''));

        $contentExtractor->parseContent('http://foo.bar.nowhere', 'default content');

        $this->assertSame('<html/>', $contentExtractor->content);
    }

    public function testLegacyExternalFeedUsesGraby(): void
    {
        $feed = new Feed();
        $feed->setParser('external');
        $contentExtractor = $this->getContentExtrator(false, $feed);
        $this->graby->expects($this->once())->method('fetchContent')->willReturn($this->getGrabyContent('<p>Graby content</p>'));

        $contentExtractor->parseContent('https://legacy.example/article');

        $this->assertSame('<p>Graby content</p>', $contentExtractor->content);
    }

    protected function getContentExtrator(bool $customExtractor = false, ?Feed $feed = null): Extractor
    {
        $feed ??= new Feed();
        $feed->setId(66);
        $feed->setSortBy('created_at');
        $feed->setFormatter('atom');
        $feed->setHost('Default');

        $extractorChain = $this->createMock(ExtractorChain::class);

        $extractorChain
            ->method('match')
            ->willReturn(false);

        if (true === $customExtractor) {
            $extractorChain = $this->createMock(ExtractorChain::class);

            $extractor = new class extends AbstractExtractor {
                public function match(string $url): bool
                {
                    return false;
                }

                public function getContent(): string
                {
                    return '<html/>';
                }
            };

            $extractorChain
                ->method('match')
                ->willReturn($extractor);
        }

        $improverChain = $this->createMock(ImproverChain::class);

        $defaultImprover = $this->createMock(DefaultImprover::class);

        $defaultImprover
            ->method('updateContent')
            ->willReturnArgument(0);

        $defaultImprover
            ->method('updateUrl')
            ->willReturnArgument(0);

        $improverChain
            ->method('match')
            ->willReturn($defaultImprover);

        $converterChain = $this->createMock(ConverterChain::class);

        $converterChain
            ->method('convert')
            ->willReturnArgument(0);

        $this->graby = $this->getMockBuilder(Graby::class)
            ->onlyMethods(['fetchContent'])
            ->disableOriginalConstructor()
            ->getMock();

        $internalParser = new Internal($this->graby);

        $contentExtractor = new Extractor($extractorChain, $improverChain, $converterChain, $internalParser);
        $contentExtractor->init($feed);

        return $contentExtractor;
    }

    private function getGrabyContent(string $html): Content
    {
        return new Content(
            new EffectiveResponse(
                new Uri('http://website.test/content.html'),
                new Response(200, [], '')
            ),
            // html
            $html,
            // title
            '',
            // language
            null,
            // date
            null,
            // authors
            [],
            // image
            null,
            // is ads
            false,
        );
    }
}
