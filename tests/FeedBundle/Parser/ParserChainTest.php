<?php

namespace tests\FeedBundle\Parser;

use Api43\FeedBundle\Parser\ParserChain;

class ParserChainTest extends \PHPUnit_Framework_TestCase
{
    public function testParseTrue()
    {
        $parser = $this->getMockBuilder('Api43\FeedBundle\Parser\AbstractParser')
            ->disableOriginalConstructor()
            ->getMock();

        $parser->expects($this->once())
            ->method('parse')
            ->will($this->returnValue('content'));

        $parserChain = new ParserChain();
        $parserChain->addParser($parser, 'alias');

        $this->assertEquals('content', $parserChain->parseAll('url'));
        $this->assertEquals($parser, $parserChain->getParser('alias'));
    }

    public function testParseFalse()
    {
        $parser = $this->getMockBuilder('Api43\FeedBundle\Parser\AbstractParser')
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
