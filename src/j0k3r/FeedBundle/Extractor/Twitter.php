<?php

namespace j0k3r\FeedBundle\Extractor;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\RequestException;

class Twitter extends AbstractExtractor
{
    protected $guzzle;
    protected $tweetId = null;

    public function __construct(Client $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    /**
     * {@inheritdoc}
     */
    public function match($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);

        if (false === $host || false === $path) {
            return false;
        }

        // find tweet id
        preg_match('/([0-9]{18})/', $path, $matches);

        if (0 !== strpos($host, 'twitter') || !isset($matches[1])) {
            return false;
        }

        $this->tweetId = $matches[1];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->tweetId) {
            return '';
        }

        try {
            $data = $this->guzzle
                ->get('https://api.twitter.com/1/statuses/oembed.json?id='.$this->tweetId)
                ->send()
                ->json();
        } catch (RequestException $e) {
            return '';
        }

        if (!isset($data['html'])) {
            return '';
        }

        return $data['html'];
    }
}
