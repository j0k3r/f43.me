<?php

namespace Api43\FeedBundle\Extractor;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\RequestException;

class Dailymotion extends AbstractExtractor
{
    protected $guzzle;
    protected $dailymotionUrl = null;

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

        if (!in_array($host, array('www.dailymotion.com', 'dailymotion.com', 'dai.ly'))) {
            return false;
        }

        $this->dailymotionUrl = $url;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->dailymotionUrl) {
            return '';
        }

        try {
            $data = $this->guzzle
                ->get('http://www.dailymotion.com/services/oembed?format=json&url='.$this->dailymotionUrl)
                ->send()
                ->json();
        } catch (RequestException $e) {
            trigger_error('Dailymotion extract failed for "'.$this->dailymotionUrl.'": '.$e->getMessage());

            return '';
        }

        if (!is_array($data) || empty($data)) {
            return '';
        }

        return '<div><h2>'.$data['title'].'</h2><p><img src="'.$data['thumbnail_url'].'"></p>'.$data['html'].'</div>';
    }
}
