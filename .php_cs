<?php

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    // use default SYMFONY_LEVEL and extra fixers:
    ->fixers([
        'concat_with_spaces',
        'ordered_use',
        'phpdoc_order',
        'strict',
        'strict_param',
        'short_array_syntax',
    ])
    ->finder(
        Symfony\CS\Finder\DefaultFinder::create()
            ->exclude(['web', 'var', 'bin', 'node_modules'])
            ->in(__DIR__)
    )
    ->setUsingCache(true)
;
