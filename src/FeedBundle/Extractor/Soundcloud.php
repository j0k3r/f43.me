<?php

namespace Api43\FeedBundle\Extractor;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\RequestException;

class Soundcloud extends AbstractExtractor
{
    protected $guzzle;
    protected $soundCloundUrl = null;

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

        if (false === strpos($host, 'soundcloud.com')) {
            return false;
        }

        $this->soundCloundUrl = $url;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->soundCloundUrl) {
            return '';
        }

        try {
            $data = $this->guzzle
                ->get('http://soundcloud.com/oembed?format=json&url='.$this->soundCloundUrl)
                ->send()
                ->json();
        } catch (RequestException $e) {
            trigger_error('Soundcloud extract failed for "'.$this->soundCloundUrl.'": '.$e->getMessage());

            return '';
        }

        if (!is_array($data) || empty($data)) {
            return '';
        }

        return '<div><h2>'.$data['title'].'</h2><p>'.$data['description'].'</p><p><img src="'.$data['thumbnail_url'].'"></p>'.$data['html'].'</div>';
    }
}
