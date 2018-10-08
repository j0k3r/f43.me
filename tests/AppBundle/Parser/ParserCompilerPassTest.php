<?php

namespace Tests\AppBundle\Parser;

use AppBundle\Parser\ParserCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ParserCompilerPassTest extends TestCase
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
            ->register('feed.parser.chain')
            ->setPublic(false)
        ;

        $container
            ->register('foo')
            ->addTag('feed.parser', ['alias' => 'internal'])
        ;

        $this->process($container);

        $this->assertTrue($container->hasDefinition('feed.parser.chain'));

        $definition = $container->getDefinition('feed.parser.chain');
        $this->assertTrue($definition->hasMethodCall('addParser'));

        $calls = $definition->getMethodCalls();
        $this->assertSame('internal', $calls[0][1][1]);
    }

    protected function process(ContainerBuilder $container)
    {
        $repeatedPass = new ParserCompilerPass();
        $repeatedPass->process($container);
    }
}
