<?php

namespace App\Tests\Converter;

use App\Converter\ConverterCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConverterCompilerPassTest extends TestCase
{
    public function testProcessNoDefinition()
    {
        $container = new ContainerBuilder();
        $res = $this->process($container);

        $this->assertNull($res);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container
            ->register('App\Converter\ConverterChain')
            ->setPublic(false)
        ;

        $container
            ->register('foo')
            ->addTag('feed.converter', ['alias' => 'instagram'])
        ;

        $this->process($container);

        $this->assertTrue($container->hasDefinition('App\Converter\ConverterChain'));

        $definition = $container->getDefinition('App\Converter\ConverterChain');
        $this->assertTrue($definition->hasMethodCall('addConverter'));

        $calls = $definition->getMethodCalls();
        $this->assertSame('instagram', $calls[0][1][1]);
    }

    protected function process(ContainerBuilder $container)
    {
        $repeatedPass = new ConverterCompilerPass();
        $repeatedPass->process($container);
    }
}
