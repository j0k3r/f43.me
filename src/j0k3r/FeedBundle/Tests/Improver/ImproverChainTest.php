<?php

namespace j0k3r\FeedBundle\Tests\Improver;

use j0k3r\FeedBundle\Improver\ImproverChain;

class ImproverChainTest extends \PHPUnit_Framework_TestCase
{
    public function testMatchTrue()
    {
        $improver = $this->getMockBuilder('j0k3r\FeedBundle\Improver\Nothing')
            ->disableOriginalConstructor()
            ->getMock();

        $improver->expects($this->once())
            ->method('match')
            ->will($this->returnValue(true));

        $improverChain = new ImproverChain();
        $improverChain->addImprover($improver, 'alias');

        $this->assertEquals($improver, $improverChain->match('host'));
    }

    public function testMatchFalse()
    {
        $improver = $this->getMockBuilder('j0k3r\FeedBundle\Improver\Nothing')
            ->disableOriginalConstructor()
            ->getMock();

        $improver->expects($this->once())
            ->method('match')
            ->will($this->returnValue(false));

        $improverChain = new ImproverChain();
        $improverChain->addImprover($improver, 'alias');

        $this->assertFalse($improverChain->match('host'));
    }
}
