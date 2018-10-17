<?php

namespace Tests\AppBundle\Converter;

use AppBundle\Converter\ConverterCompilerPass;
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
            ->register('AppBundle\Converter\ConverterChain')
            ->setPublic(false)
        ;

        $container
            ->register('foo')
            ->addTag('feed.converter', ['alias' => 'instagram'])
        ;

        $this->process($container);

        $this->assertTrue($container->hasDefinition('AppBundle\Converter\ConverterChain'));

        $definition = $container->getDefinition('AppBundle\Converter\ConverterChain');
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
