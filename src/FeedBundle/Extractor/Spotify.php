<?php

namespace Api43\FeedBundle\Extractor;

use GuzzleHttp\Exception\RequestException;

class Spotify extends AbstractExtractor
{
    protected $spotifyUrl = null;

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
            $data = $this->client
                ->get('https://embed.spotify.com/oembed/?format=json&url='.$this->spotifyUrl)
                ->json();
        } catch (RequestException $e) {
            $this->logger->warning('Spotify extract failed for: '.$this->spotifyUrl, array(
                'exception' => $e,
            ));

            return '';
        }

        if (!is_array($data) || empty($data)) {
            return '';
        }

        return '<div><h2>'.$data['title'].'</h2><p><img src="'.$data['thumbnail_url'].'"></p>'.$data['html'].'</div>';
    }
}
