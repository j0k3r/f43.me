<?php

namespace Tests\AppBundle\Converter;

use AppBundle\Converter\ConverterChain;
use PHPUnit\Framework\TestCase;

class ConverterChainTest extends TestCase
{
    public function testConvert()
    {
        $converter = $this->getMockBuilder('AppBundle\Converter\AbstractConverter')
            ->disableOriginalConstructor()
            ->getMock();

        $converter->expects($this->once())
            ->method('convert')
            ->will($this->returnValue('changed'));

        $converterChain = new ConverterChain();
        $converterChain->addConverter($converter, 'alias');

        $this->assertSame('changed', $converterChain->convert('url'));
    }
}
