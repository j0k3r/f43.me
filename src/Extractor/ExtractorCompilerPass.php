<?php

namespace App\Extractor;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ExtractorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ExtractorChain::class)) {
            return;
        }

        $definition = $container->getDefinition(
            ExtractorChain::class
        );

        $taggedServices = $container->findTaggedServiceIds(
            'feed.extractor'
        );
        foreach ($taggedServices as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                $definition->addMethodCall(
                    'addExtractor',
                    [new Reference($id), $attributes['alias']]
                );
            }
        }
    }
}
