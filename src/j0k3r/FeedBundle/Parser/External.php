<?php

namespace j0k3r\FeedBundle\Parser;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\RequestException;

/**
 * Retrieve content from an external webservice.
 * In this case, we use the excellent Readability web service: https://www.readability.com/developers/api/parser
 */
class External extends AbstractParser
{
    protected $guzzle;
    protected $urlApi;
    protected $token;

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
            $html = $this->guzzle
                ->get($this->urlApi.'?token='.$this->token.'&url='.urlencode($url))
                ->send()
                ->json();
        } catch (RequestException $e) {
            return '';
        }

        if (isset($html['content'])) {
            $this->url = $html['url'];

            return $html['content'];
        }

        if (isset($html['error'])) {
            return $html['messages'];
        }

        return '';
    }
}
