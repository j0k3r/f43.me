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
    protected $guzzle;
    protected $urlApi;
    protected $token;

    /**
     * @param Client $guzzle
     * @param string $urlApi Readability API url
     * @param string $token  Readability API token
     */
    public function __construct(Client $guzzle, $urlApi, $token)
    {
        $this->guzzle = $guzzle;
        $this->urlApi = $urlApi;
        $this->token = $token;
    }

    /**
     * {@inheritdoc}
     */
    public function parse($url)
    {
        try {
            $data = $this->guzzle
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
