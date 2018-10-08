<?php

namespace Tests\AppBundle\Extractor;

use AppBundle\Extractor\ExtractorChain;
use PHPUnit\Framework\TestCase;

class ExtractorChainTest extends TestCase
{
    public function testMatchTrue()
    {
        $extractor = $this->getMockBuilder('AppBundle\Extractor\AbstractExtractor')
            ->disableOriginalConstructor()
            ->getMock();

        $extractor->expects($this->once())
            ->method('match')
            ->will($this->returnValue(true));

        $extractorChain = new ExtractorChain();
        $extractorChain->addExtractor($extractor, 'alias');

        $this->assertSame($extractor, $extractorChain->match('url'));
    }

    public function testMatchFalse()
    {
        $extractor = $this->getMockBuilder('AppBundle\Extractor\AbstractExtractor')
            ->disableOriginalConstructor()
            ->getMock();

        $extractor->expects($this->once())
            ->method('match')
            ->will($this->returnValue(false));

        $extractorChain = new ExtractorChain();
        $extractorChain->addExtractor($extractor, 'alias');

        $this->assertFalse($extractorChain->match('url'));
    }
}
