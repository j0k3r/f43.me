<?php

namespace App\Improver;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ImproverCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ImproverChain::class)) {
            return;
        }

        $definition = $container->getDefinition(
            ImproverChain::class
        );

        $taggedServices = $container->findTaggedServiceIds(
            'feed.improver'
        );
        foreach ($taggedServices as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                $definition->addMethodCall(
                    'addImprover',
                    [new Reference($id), $attributes['alias']]
                );
            }
        }
    }
}
