<?php

namespace j0k3r\FeedBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use j0k3r\FeedBundle\Extractor\ExtractorCompilerPass;
use j0k3r\FeedBundle\Improver\ImproverCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class j0k3rFeedBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ExtractorCompilerPass());
        $container->addCompilerPass(new ImproverCompilerPass());
    }
}
