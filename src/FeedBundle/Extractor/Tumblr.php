<?php

namespace Api43\FeedBundle\Extractor;

use GuzzleHttp\Exception\RequestException;

class Tumblr extends AbstractExtractor
{
    protected $tumblrApiKey;
    protected $tumblrId = null;
    protected $tumblrHost = null;

    /**
     * @param string $tumblrApiKey
     */
    public function __construct($tumblrApiKey)
    {
        $this->tumblrApiKey = $tumblrApiKey;
    }

    /**
     * {@inheritdoc}
     */
    public function match($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);

        if (false === $path) {
            return false;
        }

        // find tumblr post id
        preg_match('/post\/([0-9]+)/', $path, $matches);

        if (!isset($matches[1])) {
            return false;
        }

        try {
            // retrieve the tumblr user to validate that's a tumblr post
            $tumblrUser = $this->client
                ->get($url)
                ->getHeader('X-Tumblr-User');
        } catch (RequestException $e) {
            $this->logger->warning('Tumblr extract failed for: ' . $url, [
                'exception' => $e,
            ]);

            return false;
        }

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
            return '';
        }

        try {
            $data = $this->client
                ->get('http://api.tumblr.com/v2/blog/' . $this->tumblrHost . '/posts/text?api_key=' . $this->tumblrApiKey . '&id=' . $this->tumblrId)
                ->json();
        } catch (RequestException $e) {
            $this->logger->warning('Tumblr extract failed for: ' . $this->tumblrId . ' & ' . $this->tumblrHost, [
                'exception' => $e,
            ]);

            return '';
        }

        if (!isset($data['response']['posts'][0]['body'])) {
            return '';
        }

        return $data['response']['posts'][0]['body'];
    }
}
