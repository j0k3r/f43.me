<?php

namespace Api43\FeedBundle\Tests\Parser;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Api43\FeedBundle\Parser\ParserCompilerPass;

class ParserCompilerPassTest extends \PHPUnit_Framework_TestCase
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
            ->addTag('feed.parser', array('alias' => 'internal'))
        ;

        $this->process($container);

        $this->assertTrue($container->hasDefinition('feed.parser.chain'));

        $definition = $container->getDefinition('feed.parser.chain');
        $this->assertTrue($definition->hasMethodCall('addParser'));

        $calls = $definition->getMethodCalls();
        $this->assertEquals('internal', $calls[0][1][1]);
    }

    protected function process(ContainerBuilder $container)
    {
        $repeatedPass = new ParserCompilerPass();
        $repeatedPass->process($container);
    }
}
