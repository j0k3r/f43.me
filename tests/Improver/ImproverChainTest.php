<?php

namespace App\Tests\Improver;

use App\Improver\ImproverChain;
use PHPUnit\Framework\TestCase;

class ImproverChainTest extends TestCase
{
    public function testMatchTrue()
    {
        $improver = $this->getMockBuilder('App\Improver\DefaultImprover')
            ->disableOriginalConstructor()
            ->getMock();

        $improver->expects($this->once())
            ->method('match')
            ->willReturn(true);

        $improverChain = new ImproverChain();
        $improverChain->addImprover($improver, 'alias');

        $this->assertSame($improver, $improverChain->match('host'));
    }

    public function testMatchFalse()
    {
        $improver = $this->getMockBuilder('App\Improver\DefaultImprover')
            ->disableOriginalConstructor()
            ->getMock();

        $improver->expects($this->once())
            ->method('match')
            ->willReturn(false);

        $improverChain = new ImproverChain();
        $improverChain->addImprover($improver, 'alias');

        $this->assertFalse($improverChain->match('host'));
    }

    public function testMatchWithEmptyHost()
    {
        $improver = $this->getMockBuilder('App\Improver\DefaultImprover')
            ->disableOriginalConstructor()
            ->getMock();

        $improver->expects($this->never())
            ->method('match');

        $improverChain = new ImproverChain();
        $improverChain->addImprover($improver, 'alias');

        $this->assertFalse($improverChain->match(''));
    }
}
