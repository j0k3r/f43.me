<?php

namespace Api43\FeedBundle\Extractor;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\RequestException;

class Vimeo extends AbstractExtractor
{
    protected $guzzle;
    protected $vimeoUrl = null;

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

        if (false === strpos($host, 'vimeo.com')) {
            return false;
        }

        $this->vimeoUrl = $url;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->vimeoUrl) {
            return '';
        }

        try {
            $data = $this->guzzle
                ->get('https://vimeo.com/api/oembed.xml?format=json&url='.$this->vimeoUrl)
                ->send()
                ->json();
        } catch (RequestException $e) {
            trigger_error('Vimeo extract failed for "'.$this->vimeoUrl.'": '.$e->getMessage());

            return '';
        }

        if (!is_array($data) || empty($data)) {
            return '';
        }

        return '<div><h2>'.$data['title'].'</h2><p>'.$data['description'].'</p><p><img src="'.$data['thumbnail_url'].'"></p>'.$data['html'].'</div>';
    }
}
