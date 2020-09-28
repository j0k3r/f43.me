<?php

namespace App\Extractor;

use Http\Client\Common\HttpMethodsClientInterface;
use Psr\Http\Message\ResponseInterface;
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

    /**
     * Generic method to retrive the json data from a response.
     *
     * @return array
     */
    protected function jsonDecode(ResponseInterface $response)
    {
        $data = json_decode((string) $response->getBody(), true);

        if (null === $data) {
            return [];
        }

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException('Unable to parse JSON data: ' . json_last_error_msg());
        }

        return $data;
    }
}
