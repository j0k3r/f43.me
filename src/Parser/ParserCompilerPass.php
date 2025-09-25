<?php

namespace App\Parser;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ParserCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ParserChain::class)) {
            return;
        }

        $definition = $container->getDefinition(
            ParserChain::class
        );

        $taggedServices = $container->findTaggedServiceIds(
            'feed.parser'
        );

        foreach ($taggedServices as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                $definition->addMethodCall(
                    'addParser',
                    [new Reference($id), $attributes['alias']]
                );
            }
        }
    }
}
