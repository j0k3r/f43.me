<?php

namespace App\Converter;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ConverterCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('App\Converter\ConverterChain')) {
            return;
        }

        $definition = $container->getDefinition(
            'App\Converter\ConverterChain'
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
