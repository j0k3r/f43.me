<?php

namespace App\Tests\Extractor;

use App\Extractor\ExtractorChain;
use PHPUnit\Framework\TestCase;

class ExtractorChainTest extends TestCase
{
    public function testMatchTrue(): void
    {
        $extractor = $this->getMockBuilder('App\Extractor\AbstractExtractor')
            ->disableOriginalConstructor()
            ->getMock();

        $extractor->expects($this->once())
            ->method('match')
            ->willReturn(true);

        $extractorChain = new ExtractorChain();
        $extractorChain->addExtractor($extractor, 'alias');

        $this->assertSame($extractor, $extractorChain->match('url'));
    }

    public function testMatchFalse(): void
    {
        $extractor = $this->getMockBuilder('App\Extractor\AbstractExtractor')
            ->disableOriginalConstructor()
            ->getMock();

        $extractor->expects($this->once())
            ->method('match')
            ->willReturn(false);

        $extractorChain = new ExtractorChain();
        $extractorChain->addExtractor($extractor, 'alias');

        $this->assertFalse($extractorChain->match('url'));
    }
}
