<?php

namespace AppBundle\Parser;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Retrieve content from an external webservice.
 * In this case, we use the excellent Mercury (the new name of Readability) web parser: https://mercury.postlight.com/web-parser/.
 */
class External extends AbstractParser
{
    protected $client;
    protected $urlApi;
    protected $key;

    /**
     * @param Client $client
     * @param string $urlApi Mercury API url
     * @param string $key    Mercury API key
     */
    public function __construct(Client $client, $urlApi, $key)
    {
        $this->client = $client;
        $this->urlApi = $urlApi;
        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function parse($url, $reloadConfigFiles = false)
    {
        try {
            $data = $this->client
                ->get(
                    $this->urlApi . '?url=' . urlencode($url),
                    [
                        'headers' => [
                            'x-api-key' => $this->key,
                        ],
                    ]
                )
                ->json();
        } catch (RequestException $e) {
            return '';
        }

        if (isset($data['content'])) {
            return $data['content'];
        }

        return '';
    }
}
