<?php

namespace App\Parser;

use Http\Client\Common\HttpMethodsClientInterface;
use Http\Client\Exception\RequestException;

/**
 * Retrieve content from an external webservice.
 * In this case, we use the excellent Mercury (the new name of Readability) parser: https://github.com/postlight/mercury-parser.
 */
class External extends AbstractParser
{
    /**
     * @param string $urlApi Mercury API url
     */
    public function __construct(protected HttpMethodsClientInterface $client, protected string $urlApi)
    {
    }

    public function parse(string $url, bool $reloadConfigFiles = false): string
    {
        try {
            $response = $this->client->get($this->urlApi . '?url=' . urlencode($url));

            $data = json_decode((string) $response->getBody(), true);
        } catch (RequestException) {
            return '';
        }

        return $data['content'] ?? '';
    }
}
