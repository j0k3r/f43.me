<?php

namespace Api43\FeedBundle;

use Api43\FeedBundle\Extractor\ExtractorCompilerPass;
use Api43\FeedBundle\Improver\ImproverCompilerPass;
use Api43\FeedBundle\Parser\ParserCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class Api43FeedBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ExtractorCompilerPass());
        $container->addCompilerPass(new ImproverCompilerPass());
        $container->addCompilerPass(new ParserCompilerPass());
    }
}
