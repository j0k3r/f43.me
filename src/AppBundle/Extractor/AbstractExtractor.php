<?php

namespace AppBundle\Extractor;

use Http\Client\Common\HttpMethodsClientInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractExtractor implements LoggerAwareInterface
{
    protected $logger;
    protected $client;

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param HttpMethodsClientInterface $client
     */
    public function setClient(HttpMethodsClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Will tell if this url should be handled by this extrator.
     *
     * @param string $url
     *
     * @return bool
     */
    abstract public function match($url);

    /**
     * Return the content from the extractor.
     *
     * @return string|false Content expanded
     */
    abstract public function getContent();
}
