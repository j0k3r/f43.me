<?php

namespace App\Tests\Converter;

use App\Converter\AbstractConverter;
use App\Converter\ConverterChain;
use PHPUnit\Framework\TestCase;

class ConverterChainTest extends TestCase
{
    public function testConvert(): void
    {
        $converter = $this->getMockBuilder(AbstractConverter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $converter->expects($this->once())
            ->method('convert')
            ->willReturn('changed');

        $converterChain = new ConverterChain();
        $converterChain->addConverter($converter, 'alias');

        $this->assertSame('changed', $converterChain->convert('url'));
    }
}
