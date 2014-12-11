<?php

namespace Api43\FeedBundle\DependencyInjection;

use Api43\FeedBundle\DependencyInjection\Api43FeedExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Api43FeedExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfigWithDefaultValues()
    {
        $extension = new Api43FeedExtension();
        $extension->load(array(), $container = new ContainerBuilder());

        // well no custom configuration loaded
        $this->assertEmpty($container->getParameterBag()->all());
    }
}
