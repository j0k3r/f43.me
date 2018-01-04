<?php

namespace Api43\FeedBundle\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Api43FeedExtensionTest extends TestCase
{
    public function testGetConfigWithDefaultValues()
    {
        $extension = new Api43FeedExtension();
        $extension->load([], $container = new ContainerBuilder());

        // well no custom configuration loaded
        $this->assertEmpty($container->getParameterBag()->all());
    }
}
