<?php

namespace Api43\FeedBundle\Extractor;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Spotify extends AbstractExtractor
{
    protected $guzzle;
    protected $spotifyUrl = null;

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

        if (!in_array($host, array('open.spotify.com', 'play.spotify.com'))) {
            return false;
        }

        $this->spotifyUrl = $url;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->spotifyUrl) {
            return '';
        }

        try {
            $data = $this->guzzle
                ->get('https://embed.spotify.com/oembed/?format=json&url='.$this->spotifyUrl)
                ->json();
        } catch (RequestException $e) {
            trigger_error('Spotify extract failed for "'.$this->spotifyUrl.'": '.$e->getMessage());

            return '';
        }

        if (!is_array($data) || empty($data)) {
            return '';
        }

        return '<div><h2>'.$data['title'].'</h2><p><img src="'.$data['thumbnail_url'].'"></p>'.$data['html'].'</div>';
    }
}
