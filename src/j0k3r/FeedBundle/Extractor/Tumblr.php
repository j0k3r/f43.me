<?php

namespace j0k3r\FeedBundle\Extractor;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\RequestException;

class Tumblr extends AbstractExtractor
{
    protected $guzzle;
    protected $tumblrApiKey;
    protected $tumblrId = null;
    protected $tumblrHost = null;

    public function __construct(Client $guzzle, $tumblrApiKey)
    {
        $this->guzzle = $guzzle;
        $this->tumblrApiKey = $tumblrApiKey;
    }

    /**
     * {@inheritdoc}
     */
    public function match($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);

        // find tumblr post id
        preg_match('/post\/([0-9]{11})/', $path, $matches);

        if (!isset($matches[1])) {
            return false;
        }

        // retrieve the tumblr user to validate that's a tumblr post
        $tumblrUser = $this->guzzle
            ->get($url)
            ->send()
            ->getHeader('X-Tumblr-User');

        if (!$tumblrUser) {
            return false;
        }

        $this->tumblrId = $matches[1];
        $this->tumblrHost = $host;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->tumblrId && !$this->tumblrHost) {
            return false;
        }

        try {
            $data = $this->guzzle
                ->get('http://api.tumblr.com/v2/blog/'.$this->tumblrHost.'/posts/text?api_key='.$this->tumblrApiKey.'&id='.$this->tumblrId)
                ->send()
                ->json();
        } catch (RequestException $e) {
            return false;
        }

        if (!isset($data['response']['posts'][0]['body'])) {
            return false;
        }

        return $data['response']['posts'][0]['body'];
    }
}
