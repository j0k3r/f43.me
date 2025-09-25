<?php

namespace App\Tests\Extractor;

use App\Extractor\ExtractorChain;
use App\Extractor\ExtractorCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ExtractorCompilerPassTest extends TestCase
{
    public function testProcessNoDefinition(): void
    {
        $container = new ContainerBuilder();
        $this->process($container);

        $this->assertFalse($container->hasDefinition(ExtractorChain::class));
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $container
            ->register(ExtractorChain::class)
            ->setPublic(false)
        ;

        $container
            ->register('foo')
            ->addTag('feed.extractor', ['alias' => 'imgur'])
        ;

        $this->process($container);

        $this->assertTrue($container->hasDefinition(ExtractorChain::class));

        $definition = $container->getDefinition(ExtractorChain::class);
        $this->assertTrue($definition->hasMethodCall('addExtractor'));

        $calls = $definition->getMethodCalls();
        $this->assertSame('imgur', $calls[0][1][1]);
    }

    protected function process(ContainerBuilder $container): void
    {
        $repeatedPass = new ExtractorCompilerPass();
        $repeatedPass->process($container);
    }
}
