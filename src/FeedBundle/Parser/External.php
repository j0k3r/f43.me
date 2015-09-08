<?php

namespace Api43\FeedBundle\Parser;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Retrieve content from an external webservice.
 * In this case, we use the excellent Readability web service: https://www.readability.com/developers/api/parser.
 */
class External extends AbstractParser
{
    protected $client;
    protected $urlApi;
    protected $token;

    /**
     * @param Client $client
     * @param string $urlApi Readability API url
     * @param string $token  Readability API token
     */
    public function __construct(Client $client, $urlApi, $token)
    {
        $this->client = $client;
        $this->urlApi = $urlApi;
        $this->token = $token;
    }

    /**
     * {@inheritdoc}
     */
    public function parse($url)
    {
        try {
            $data = $this->client
                ->get($this->urlApi.'?token='.$this->token.'&url='.urlencode($url))
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
