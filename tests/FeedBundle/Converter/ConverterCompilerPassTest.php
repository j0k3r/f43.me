<?php

namespace Tests\FeedBundle\Converter;

use Api43\FeedBundle\Converter\ConverterCompilerPass;
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
            ->register('feed.converter.chain')
            ->setPublic(false)
        ;

        $container
            ->register('foo')
            ->addTag('feed.converter', ['alias' => 'instagram'])
        ;

        $this->process($container);

        $this->assertTrue($container->hasDefinition('feed.converter.chain'));

        $definition = $container->getDefinition('feed.converter.chain');
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
