<?php

namespace Tests\FeedBundle\Converter;

use Api43\FeedBundle\Converter\ConverterChain;

class ConverterChainTest extends \PHPUnit_Framework_TestCase
{
    public function testConvert()
    {
        $converter = $this->getMockBuilder('Api43\FeedBundle\Converter\AbstractConverter')
            ->disableOriginalConstructor()
            ->getMock();

        $converter->expects($this->once())
            ->method('convert')
            ->will($this->returnValue('changed'));

        $converterChain = new ConverterChain();
        $converterChain->addConverter($converter, 'alias');

        $this->assertEquals('changed', $converterChain->convert('url'));
    }
}
