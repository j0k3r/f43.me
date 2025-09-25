<?php

namespace App\Tests\Converter;

use App\Converter\ConverterChain;
use App\Converter\ConverterCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConverterCompilerPassTest extends TestCase
{
    public function testProcessNoDefinition(): void
    {
        $container = new ContainerBuilder();
        $this->process($container);

        $this->assertFalse($container->hasDefinition(ConverterChain::class));
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $container
            ->register(ConverterChain::class)
            ->setPublic(false)
        ;

        $container
            ->register('foo')
            ->addTag('feed.converter', ['alias' => 'instagram'])
        ;

        $this->process($container);

        $this->assertTrue($container->hasDefinition(ConverterChain::class));

        $definition = $container->getDefinition(ConverterChain::class);
        $this->assertTrue($definition->hasMethodCall('addConverter'));

        $calls = $definition->getMethodCalls();
        $this->assertSame('instagram', $calls[0][1][1]);
    }

    protected function process(ContainerBuilder $container): void
    {
        $repeatedPass = new ConverterCompilerPass();
        $repeatedPass->process($container);
    }
}
