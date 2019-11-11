<?php

namespace AppBundle\Parser;

use Http\Client\Common\HttpMethodsClientInterface;
use Http\Client\Exception\RequestException;

/**
 * Retrieve content from an external webservice.
 * In this case, we use the excellent Mercury (the new name of Readability) parser: https://github.com/postlight/mercury-parser.
 */
class External extends AbstractParser
{
    protected $client;
    protected $urlApi;

    /**
     * @param string $urlApi Mercury API url
     */
    public function __construct(HttpMethodsClientInterface $client, $urlApi)
    {
        $this->client = $client;
        $this->urlApi = $urlApi;
    }

    /**
     * {@inheritdoc}
     */
    public function parse($url, $reloadConfigFiles = false)
    {
        try {
            $response = $this->client->get($this->urlApi . '?url=' . urlencode($url));

            $data = json_decode((string) $response->getBody(), true);
        } catch (RequestException $e) {
            return '';
        }

        if (isset($data['content'])) {
            return $data['content'];
        }

        return '';
    }
}
