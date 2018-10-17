<?php

namespace AppBundle;

use AppBundle\Converter\ConverterCompilerPass;
use AppBundle\Extractor\ExtractorCompilerPass;
use AppBundle\Improver\ImproverCompilerPass;
use AppBundle\Parser\ParserCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AppBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ExtractorCompilerPass());
        $container->addCompilerPass(new ConverterCompilerPass());
        $container->addCompilerPass(new ImproverCompilerPass());
        $container->addCompilerPass(new ParserCompilerPass());
    }
}
