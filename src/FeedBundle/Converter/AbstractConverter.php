<?php

namespace Api43\FeedBundle\Converter;

use GuzzleHttp\Client;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractConverter implements LoggerAwareInterface
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
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
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
