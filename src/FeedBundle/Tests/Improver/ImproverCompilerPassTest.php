<?php

namespace Api43\FeedBundle\Tests\Improver;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Api43\FeedBundle\Improver\ImproverCompilerPass;

class ImproverCompilerPassTest extends \PHPUnit_Framework_TestCase
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
            ->register('feed.improver.chain')
            ->setPublic(false)
        ;

        $container
            ->register('foo')
            ->addTag('feed.improver', array('alias' => 'hackernews'))
        ;

        $this->process($container);

        $this->assertTrue($container->hasDefinition('feed.improver.chain'));

        $definition = $container->getDefinition('feed.improver.chain');
        $this->assertTrue($definition->hasMethodCall('addImprover'));

        $calls = $definition->getMethodCalls();
        $this->assertEquals('hackernews', $calls[0][1][1]);
    }

    protected function process(ContainerBuilder $container)
    {
        $repeatedPass = new ImproverCompilerPass();
        $repeatedPass->process($container);
    }
}
