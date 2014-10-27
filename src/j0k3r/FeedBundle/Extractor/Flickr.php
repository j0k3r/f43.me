<?php

namespace j0k3r\FeedBundle\Extractor;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\RequestException;

class Flickr extends AbstractExtractor
{
    protected $guzzle;
    protected $flickrApiKey;
    protected $flickrId = null;

    /**
     *
     * @param Client $guzzle
     * @param string $flickrApiKey
     */
    public function __construct(Client $guzzle, $flickrApiKey)
    {
        $this->guzzle = $guzzle;
        $this->flickrApiKey = $flickrApiKey;
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

        if (false === strpos($host, 'flickr.com')) {
            return false;
        }

        // find flickr id: /15000967102
        preg_match('/\/([0-9]{11})/', $path, $matches);

        if (!isset($matches[1])) {
            return false;
        }

        $this->flickrId = $matches[1];

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @see https://www.flickr.com/services/api/explore/flickr.photos.getSizes
     */
    public function getContent()
    {
        if (!$this->flickrId) {
            return '';
        }

        try {
            $data = $this->guzzle
                ->get('https://api.flickr.com/services/rest/?method=flickr.photos.getSizes&api_key='.$this->flickrApiKey.'&photo_id='.$this->flickrId.'&format=json&nojsoncallback=1')
                ->send()
                ->json();
        } catch (RequestException $e) {
            trigger_error('Flickr extract failed for "'.$this->flickrId.'": '.$e->getMessage());

            return '';
        }

        if (empty($data) || (isset($data['stat']) && $data['stat'] != 'ok')) {
            return '';
        }

        // the biggest photo is always the last one
        $size = end($data['sizes']['size']);

        return '<img src="'.$size['source'].'" />';
    }
}
