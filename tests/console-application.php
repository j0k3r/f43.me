<?php

/**
 * @see https://github.com/phpstan/phpstan-symfony/pull/45
 */
require __DIR__ . '/../vendor/autoload.php';

$kernel = new AppKernel('test', true);

return new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
