<?php

namespace Tests\AppBundle;

use AppBundle\AppBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AppBundleTest extends TestCase
{
    public function testInitBundle()
    {
        $container = new ContainerBuilder();
        $bundle = new AppBundle();
        $bundle->build($container);

        $passes = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();

        // ensure at least our own compiler are loaded
        $this->assertGreaterThan(4, $passes);
    }
}
