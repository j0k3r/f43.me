<?php

namespace App;

use App\Converter\ConverterCompilerPass;
use App\Extractor\ExtractorCompilerPass;
use App\Improver\ImproverCompilerPass;
use App\Parser\ParserCompilerPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ExtractorCompilerPass());
        $container->addCompilerPass(new ConverterCompilerPass());
        $container->addCompilerPass(new ImproverCompilerPass());
        $container->addCompilerPass(new ParserCompilerPass());
    }
}
