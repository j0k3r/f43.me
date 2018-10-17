<?php

namespace Tests\AppBundle\Parser;

use AppBundle\Parser\ParserChain;
use PHPUnit\Framework\TestCase;

class ParserChainTest extends TestCase
{
    public function testParseTrue()
    {
        $parser = $this->getMockBuilder('AppBundle\Parser\AbstractParser')
            ->disableOriginalConstructor()
            ->getMock();

        $parser->expects($this->once())
            ->method('parse')
            ->will($this->returnValue('content'));

        $parserChain = new ParserChain();
        $parserChain->addParser($parser, 'alias');

        $this->assertSame('content', $parserChain->parseAll('url'));
        $this->assertSame($parser, $parserChain->getParser('alias'));
    }

    public function testParseFalse()
    {
        $parser = $this->getMockBuilder('AppBundle\Parser\AbstractParser')
            ->disableOriginalConstructor()
            ->getMock();

        $parser->expects($this->once())
            ->method('parse')
            ->will($this->returnValue(false));

        $parserChain = new ParserChain();
        $parserChain->addParser($parser, 'alias');

        $this->assertEmpty($parserChain->parseAll('url'));
        $this->assertFalse($parserChain->getParser('unexist'));
    }
}
