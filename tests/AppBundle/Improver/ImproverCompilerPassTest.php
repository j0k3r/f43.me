<?php

namespace Tests\AppBundle\Improver;

use AppBundle\Improver\ImproverCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ImproverCompilerPassTest extends TestCase
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
            ->register('AppBundle\Improver\ImproverChain')
            ->setPublic(false)
        ;

        $container
            ->register('foo')
            ->addTag('feed.improver', ['alias' => 'hackernews'])
        ;

        $this->process($container);

        $this->assertTrue($container->hasDefinition('AppBundle\Improver\ImproverChain'));

        $definition = $container->getDefinition('AppBundle\Improver\ImproverChain');
        $this->assertTrue($definition->hasMethodCall('addImprover'));

        $calls = $definition->getMethodCalls();
        $this->assertSame('hackernews', $calls[0][1][1]);
    }

    protected function process(ContainerBuilder $container)
    {
        $repeatedPass = new ImproverCompilerPass();
        $repeatedPass->process($container);
    }
}
