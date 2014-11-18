<?php

namespace j0k3r\FeedBundle\Extractor;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\RequestException;

class Flickr extends AbstractExtractor
{
    protected $guzzle;
    protected $flickrApiKey;
    protected $flickrId = null;
    protected $flickrSetId = null;

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

        if (1 === preg_match('/\/([0-9]{17})/', $path, $matches)) {
            // find flickr photoSet id: /72157638315605535
            $this->flickrSetId = $matches[1];
        } elseif (1 === preg_match('/\/([0-9]{11})/', $path, $matches)) {
            // find flickr photo id: /15000967102
            $this->flickrId = $matches[1];
        } else {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if ($this->flickrId) {
            return $this->getPhoto();
        } elseif ($this->flickrSetId) {
            return $this->getPhotoFromSet();
        }

        return '';
    }

    /**
     * Grab one photo from Flickr
     *
     * @see https://www.flickr.com/services/api/explore/flickr.photos.getSizes
     *
     * @return string
     */
    private function getPhoto()
    {
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

    /**
     * Grab photos from a photo set
     *
     * @see https://www.flickr.com/services/api/flickr.photosets.getPhotos.htm
     *
     * @return string
     */
    private function getPhotoFromSet()
    {
        try {
            $data = $this->guzzle
                ->get('https://api.flickr.com/services/rest/?method=flickr.photosets.getPhotos&api_key='.$this->flickrApiKey.'&photoset_id='.$this->flickrSetId.'&extras=url_l,url_o&format=json&nojsoncallback=1')
                ->send()
                ->json();
        } catch (RequestException $e) {
            trigger_error('Flickr extract failed for "'.$this->flickrSetId.'": '.$e->getMessage());

            return '';
        }

        if (empty($data) || (isset($data['stat']) && $data['stat'] != 'ok')) {
            return '';
        }

        $content = '';

        foreach ($data['photoset']['photo'] as $photo) {
            $url = isset($photo['url_l']) ? $photo['url_l'] : $photo['url_o'];

            $content .= '<div><p>'.$photo['title'].'</p>';
            $content .= '<img src="'.$url.'" />';
            $content .= '</div>';
        }

        return $content;
    }
}
