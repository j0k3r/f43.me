<?php

namespace Api43\FeedBundle\Extractor;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\RequestException;

class Youtube extends AbstractExtractor
{
    protected $guzzle;
    protected $youtubeUrl = null;

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

        if (!in_array($host, array('www.youtube.com', 'youtube.com', 'youtu.be'))) {
            return false;
        }

        $this->youtubeUrl = $url;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->youtubeUrl) {
            return '';
        }

        try {
            $data = $this->guzzle
                ->get('http://www.youtube.com/oembed?format=json&url='.$this->youtubeUrl)
                ->send()
                ->json();
        } catch (RequestException $e) {
            trigger_error('Youtube extract failed for "'.$this->youtubeUrl.'": '.$e->getMessage());

            return '';
        }

        if (!is_array($data) || empty($data)) {
            return '';
        }

        return '<div><h2>'.$data['title'].'</h2><p><img src="'.$data['thumbnail_url'].'"></p>'.$data['html'].'</div>';
    }
}
