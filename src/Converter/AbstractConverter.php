<?php

namespace App\Converter;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractConverter implements LoggerAwareInterface
{
    protected $logger;

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Will convert the html into what they want.
     * If it hasn't modified, it'll return right away.
     *
     * @param string $html
     *
     * @return string
     */
    abstract public function convert($html);
}
