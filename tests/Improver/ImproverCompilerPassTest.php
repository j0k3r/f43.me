<?php

namespace App\Tests\Improver;

use App\Improver\ImproverCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ImproverCompilerPassTest extends TestCase
{
    public function testProcessNoDefinition(): void
    {
        $container = new ContainerBuilder();
        $this->process($container);

        $this->assertFalse($container->hasDefinition('App\Improver\ImproverChain'));
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $container
            ->register('App\Improver\ImproverChain')
            ->setPublic(false)
        ;

        $container
            ->register('foo')
            ->addTag('feed.improver', ['alias' => 'hackernews'])
        ;

        $this->process($container);

        $this->assertTrue($container->hasDefinition('App\Improver\ImproverChain'));

        $definition = $container->getDefinition('App\Improver\ImproverChain');
        $this->assertTrue($definition->hasMethodCall('addImprover'));

        $calls = $definition->getMethodCalls();
        $this->assertSame('hackernews', $calls[0][1][1]);
    }

    protected function process(ContainerBuilder $container): void
    {
        $repeatedPass = new ImproverCompilerPass();
        $repeatedPass->process($container);
    }
}
