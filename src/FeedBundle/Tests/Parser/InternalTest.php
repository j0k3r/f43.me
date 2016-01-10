<?php

namespace Api43\FeedBundle\Tests\Parser;

use Api43\FeedBundle\Parser\Internal;

class InternalTest extends \PHPUnit_Framework_TestCase
{
    public function testParseEmpty()
    {
        $graby = $this->getMockBuilder('Graby\Graby')
            ->setMethods(['fetchContent'])
            ->disableOriginalConstructor()
            ->getMock();

        $graby->expects($this->any())
            ->method('fetchContent')
            ->willReturn(false);

        $internal = new Internal($graby);
        $this->assertEmpty($internal->parse('http://localhost'));
    }

    public function testParseFalse()
    {
        $graby = $this->getMockBuilder('Graby\Graby')
            ->setMethods(['fetchContent'])
            ->disableOriginalConstructor()
            ->getMock();

        $graby->expects($this->any())
            ->method('fetchContent')
            ->willReturn(['html' => false]);

        $internal = new Internal($graby);
        $this->assertEmpty($internal->parse('http://localhost'));
    }

    public function testParseOk()
    {
        $graby = $this->getMockBuilder('Graby\Graby')
            ->setMethods(['fetchContent'])
            ->disableOriginalConstructor()
            ->getMock();

        $graby->expects($this->any())
            ->method('fetchContent')
            ->willReturn(['html' => '<p>test</p>']);

        $internal = new Internal($graby);
        $this->assertNotEmpty($internal->parse('http://localhost'));
    }

    public function testParseException()
    {
        $graby = $this->getMockBuilder('Graby\Graby')
            ->setMethods(['fetchContent'])
            ->disableOriginalConstructor()
            ->getMock();

        $graby->expects($this->any())
            ->method('fetchContent')
            ->will($this->throwException(new \Exception()));

        $internal = new Internal($graby);
        $this->assertEmpty($internal->parse('http://localhost'));
    }
}
