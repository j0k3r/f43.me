<?php

namespace AppBundle\Improver;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ImproverCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('AppBundle\Improver\ImproverChain')) {
            return;
        }

        $definition = $container->getDefinition(
            'AppBundle\Improver\ImproverChain'
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
