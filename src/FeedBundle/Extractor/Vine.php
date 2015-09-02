<?php

namespace Api43\FeedBundle\Extractor;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Vine extends AbstractExtractor
{
    protected $guzzle;
    protected $vineId = null;

    /**
     * @param Client $guzzle
     */
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

        if (false === strpos($host, 'vine.co')) {
            return false;
        }

        // find vine id
        preg_match('/^\/v\/([a-zA-Z0-9]+)/', $path, $matches);

        if (!isset($matches[1])) {
            return false;
        }

        $this->vineId = $matches[1];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->vineId) {
            return '';
        }

        try {
            $data = $this->guzzle
                ->get('https://vine.co/oembed.json?id='.$this->vineId)
                ->json();
        } catch (RequestException $e) {
            trigger_error('Vine extract failed for "'.$this->vineId.'": '.$e->getMessage());

            return '';
        }

        if (!is_array($data) || empty($data)) {
            return '';
        }

        return '<div><h2>'.$data['title'].'</h2><p><img src="'.$data['thumbnail_url'].'"></p>'.$data['html'].'</div>';
    }
}
