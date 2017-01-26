<?php

namespace Api43\FeedBundle\Converter;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ConverterCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('feed.converter.chain')) {
            return;
        }

        $definition = $container->getDefinition(
            'feed.converter.chain'
        );

        $taggedServices = $container->findTaggedServiceIds(
            'feed.converter'
        );
        foreach ($taggedServices as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                $definition->addMethodCall(
                    'addConverter',
                    [new Reference($id), $attributes['alias']]
                );
            }
        }
    }
}
