<?php

namespace Api43\FeedBundle\Improver;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class ImproverCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('feed.improver.chain')) {
            return;
        }

        $definition = $container->getDefinition(
            'feed.improver.chain'
        );

        $taggedServices = $container->findTaggedServiceIds(
            'feed.improver'
        );
        foreach ($taggedServices as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                $definition->addMethodCall(
                    'addImprover',
                    array(new Reference($id), $attributes['alias'])
                );
            }
        }
    }
}
