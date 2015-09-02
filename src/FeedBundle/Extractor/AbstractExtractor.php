<?php

namespace Api43\FeedBundle\Extractor;

use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;

abstract class AbstractExtractor
{
    protected $logger;
    protected $guzzle;

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param Client $guzzle
     */
    public function setGuzzle(Client $guzzle)
    {
        $this->guzzle = $guzzle;
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
