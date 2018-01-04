<?php

namespace Tests\FeedBundle;

use Api43\FeedBundle\Api43FeedBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Api43FeedBundleTest extends TestCase
{
    public function testInitBundle()
    {
        $container = new ContainerBuilder();
        $bundle = new Api43FeedBundle();
        $bundle->build($container);

        $passes = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();

        // ensure at least our own compiler are loaded
        $this->assertGreaterThan(4, $passes);
    }
}
