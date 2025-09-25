<?php

namespace App\Tests\Parser;

use App\Parser\Internal;
use Graby\Content;
use PHPUnit\Framework\TestCase;

class InternalTest extends TestCase
{
    public function testParseEmpty(): void
    {
        $graby = $this->getMockBuilder('Graby\Graby')
            ->onlyMethods(['fetchContent'])
            ->disableOriginalConstructor()
            ->getMock();

        $graby->expects($this->any())
            ->method('fetchContent')
            ->willReturn(new Content(200, '', '', '', '', [], '', '', [], false));

        $internal = new Internal($graby);
        $this->assertEmpty($internal->parse('http://localhost'));
    }

    public function testParseFalse(): void
    {
        $graby = $this->getMockBuilder('Graby\Graby')
            ->onlyMethods(['fetchContent'])
            ->disableOriginalConstructor()
            ->getMock();

        $graby->expects($this->any())
            ->method('fetchContent')
            ->willReturn(new Content(200, '', '', '', '', [], '', '', [], false));

        $internal = new Internal($graby);
        $this->assertEmpty($internal->parse('http://localhost'));
    }

    public function testParseOk(): void
    {
        $graby = $this->getMockBuilder('Graby\Graby')
            ->onlyMethods(['fetchContent'])
            ->disableOriginalConstructor()
            ->getMock();

        $graby->expects($this->any())
            ->method('fetchContent')
            ->willReturn(new Content(200, '<p>test</p>', '', '', '', [], '', '', [], false));

        $internal = new Internal($graby);
        $this->assertNotEmpty($internal->parse('http://localhost'));
    }

    public function testParseException(): void
    {
        $graby = $this->getMockBuilder('Graby\Graby')
            ->onlyMethods(['fetchContent'])
            ->disableOriginalConstructor()
            ->getMock();

        $graby->expects($this->any())
            ->method('fetchContent')
            ->will($this->throwException(new \Exception()));

        $internal = new Internal($graby);
        $this->assertEmpty($internal->parse('http://localhost'));
    }
}
