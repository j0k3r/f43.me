<?php

namespace App\Tests\Parser;

use App\Parser\ParserCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ParserCompilerPassTest extends TestCase
{
    public function testProcessNoDefinition(): void
    {
        $container = new ContainerBuilder();
        $this->process($container);

        $this->assertFalse($container->hasDefinition('App\Parser\ParserChain'));
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $container
            ->register('App\Parser\ParserChain')
            ->setPublic(false)
        ;

        $container
            ->register('foo')
            ->addTag('feed.parser', ['alias' => 'internal'])
        ;

        $this->process($container);

        $this->assertTrue($container->hasDefinition('App\Parser\ParserChain'));

        $definition = $container->getDefinition('App\Parser\ParserChain');
        $this->assertTrue($definition->hasMethodCall('addParser'));

        $calls = $definition->getMethodCalls();
        $this->assertSame('internal', $calls[0][1][1]);
    }

    protected function process(ContainerBuilder $container): void
    {
        $repeatedPass = new ParserCompilerPass();
        $repeatedPass->process($container);
    }
}
