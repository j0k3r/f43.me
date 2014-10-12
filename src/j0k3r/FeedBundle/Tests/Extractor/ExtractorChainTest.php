<?php

namespace j0k3r\FeedBundle\Tests\Extractor;

use j0k3r\FeedBundle\Extractor\ExtractorChain;

class ExtractorChainTest extends \PHPUnit_Framework_TestCase
{
    public function testMatchTrue()
    {
        $extractor = $this->getMockBuilder('j0k3r\FeedBundle\Extractor\AbstractExtractor')
            ->disableOriginalConstructor()
            ->getMock();

        $extractor->expects($this->once())
            ->method('match')
            ->will($this->returnValue(true));

        $extractorChain = new ExtractorChain();
        $extractorChain->addExtractor($extractor, 'alias');

        $this->assertEquals($extractor, $extractorChain->match('url'));
    }

    public function testMatchFalse()
    {
        $extractor = $this->getMockBuilder('j0k3r\FeedBundle\Extractor\AbstractExtractor')
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
