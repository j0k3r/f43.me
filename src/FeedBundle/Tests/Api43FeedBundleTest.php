<?php

namespace Api43\FeedBundle\Tests;

use Api43\FeedBundle\Api43FeedBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Api43FeedBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testInitBundle()
    {
        $container = new ContainerBuilder();
        $bundle = new Api43FeedBundle();
        $bundle->build($container);
    }
}
