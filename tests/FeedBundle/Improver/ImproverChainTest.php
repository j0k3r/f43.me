<?php

namespace Tests\FeedBundle\Improver;

use Api43\FeedBundle\Improver\ImproverChain;
use PHPUnit\Framework\TestCase;

class ImproverChainTest extends TestCase
{
    public function testMatchTrue()
    {
        $improver = $this->getMockBuilder('Api43\FeedBundle\Improver\DefaultImprover')
            ->disableOriginalConstructor()
            ->getMock();

        $improver->expects($this->once())
            ->method('match')
            ->will($this->returnValue(true));

        $improverChain = new ImproverChain();
        $improverChain->addImprover($improver, 'alias');

        $this->assertSame($improver, $improverChain->match('host'));
    }

    public function testMatchFalse()
    {
        $improver = $this->getMockBuilder('Api43\FeedBundle\Improver\DefaultImprover')
            ->disableOriginalConstructor()
            ->getMock();

        $improver->expects($this->once())
            ->method('match')
            ->will($this->returnValue(false));

        $improverChain = new ImproverChain();
        $improverChain->addImprover($improver, 'alias');

        $this->assertFalse($improverChain->match('host'));
    }

    public function testMatchWithEmptyHost()
    {
        $improver = $this->getMockBuilder('Api43\FeedBundle\Improver\DefaultImprover')
            ->disableOriginalConstructor()
            ->getMock();

        $improver->expects($this->never())
            ->method('match');

        $improverChain = new ImproverChain();
        $improverChain->addImprover($improver, 'alias');

        $this->assertFalse($improverChain->match(''));
    }
}
