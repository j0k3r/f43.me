<?php

namespace App\Tests\Extractor;

use App\Extractor\ExtractorCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ExtractorCompilerPassTest extends TestCase
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
            ->register('App\Extractor\ExtractorChain')
            ->setPublic(false)
        ;

        $container
            ->register('foo')
            ->addTag('feed.extractor', ['alias' => 'imgur'])
        ;

        $this->process($container);

        $this->assertTrue($container->hasDefinition('App\Extractor\ExtractorChain'));

        $definition = $container->getDefinition('App\Extractor\ExtractorChain');
        $this->assertTrue($definition->hasMethodCall('addExtractor'));

        $calls = $definition->getMethodCalls();
        $this->assertSame('imgur', $calls[0][1][1]);
    }

    protected function process(ContainerBuilder $container)
    {
        $repeatedPass = new ExtractorCompilerPass();
        $repeatedPass->process($container);
    }
}
