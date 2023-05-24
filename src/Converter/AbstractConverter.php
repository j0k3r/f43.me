<?php

namespace App\Converter;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractConverter implements LoggerAwareInterface
{
    /** @var LoggerInterface */
    protected $logger;

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Will convert the html into what they want.
     * If it hasn't modified, it'll return right away.
     */
    abstract public function convert(string $html): string;
}
