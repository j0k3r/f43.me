<?php

namespace App\Tests\Parser;

use App\Parser\ParserChain;
use PHPUnit\Framework\TestCase;

class ParserChainTest extends TestCase
{
    public function testParseTrue(): void
    {
        $parser = $this->getMockBuilder('App\Parser\AbstractParser')
            ->disableOriginalConstructor()
            ->getMock();

        $parser->expects($this->once())
            ->method('parse')
            ->willReturn('content');

        $parserChain = new ParserChain();
        $parserChain->addParser($parser, 'alias');

        $this->assertSame('content', $parserChain->parseAll('url'));
        $this->assertSame($parser, $parserChain->getParser('alias'));
    }

    public function testParseFalse(): void
    {
        $parser = $this->getMockBuilder('App\Parser\AbstractParser')
            ->disableOriginalConstructor()
            ->getMock();

        $parser->expects($this->once())
            ->method('parse')
            ->willReturn('');

        $parserChain = new ParserChain();
        $parserChain->addParser($parser, 'alias');

        $this->assertEmpty($parserChain->parseAll('url'));
        $this->assertFalse($parserChain->getParser('unexist'));
    }
}
