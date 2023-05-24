<?php

namespace App\Extractor;

use Http\Client\Common\HttpMethodsClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractExtractor implements LoggerAwareInterface
{
    /** @var LoggerInterface */
    protected $logger;
    /** @var HttpMethodsClientInterface */
    protected $client;

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setClient(HttpMethodsClientInterface $client): void
    {
        $this->client = $client;
    }

    /**
     * Will tell if this url should be handled by this extrator.
     */
    abstract public function match(string $url): bool;

    /**
     * Return the content from the extractor.
     *
     * @return string Content expanded
     */
    abstract public function getContent(): string;

    /**
     * Generic method to retrive the json data from a response.
     */
    protected function jsonDecode(ResponseInterface $response): array
    {
        $data = json_decode((string) $response->getBody(), true);

        if (null === $data || '' === $data) {
            return [];
        }

        if (\JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException('Unable to parse JSON data: ' . json_last_error_msg());
        }

        return $data;
    }
}
