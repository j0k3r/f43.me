<?php

namespace Api43\FeedBundle\Extractor;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class ExtractorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('feed.extractor.chain')) {
            return;
        }

        $definition = $container->getDefinition(
            'feed.extractor.chain'
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
