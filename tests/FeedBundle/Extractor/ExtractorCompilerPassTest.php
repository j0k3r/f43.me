<?php

namespace tests\FeedBundle\Extractor;

use Api43\FeedBundle\Extractor\ExtractorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ExtractorCompilerPassTest extends \PHPUnit_Framework_TestCase
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
            ->register('feed.extractor.chain')
            ->setPublic(false)
        ;

        $container
            ->register('foo')
            ->addTag('feed.extractor', ['alias' => 'imgur'])
        ;

        $this->process($container);

        $this->assertTrue($container->hasDefinition('feed.extractor.chain'));

        $definition = $container->getDefinition('feed.extractor.chain');
        $this->assertTrue($definition->hasMethodCall('addExtractor'));

        $calls = $definition->getMethodCalls();
        $this->assertEquals('imgur', $calls[0][1][1]);
    }

    protected function process(ContainerBuilder $container)
    {
        $repeatedPass = new ExtractorCompilerPass();
        $repeatedPass->process($container);
    }
}
