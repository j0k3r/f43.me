<?php

namespace AppBundle\Extractor;

use GuzzleHttp\Exception\RequestException;

class Flickr extends AbstractExtractor
{
    protected $flickrUrl = null;

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

        $this->flickrUrl = $url;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->flickrUrl) {
            return '';
        }

        try {
            $data = $this->client
                // ->get('https://api.flickr.com/services/rest/?method=flickr.photos.getSizes&api_key='.$this->flickrApiKey.'&photo_id='.$this->flickrId.'&format=json&nojsoncallback=1')
                ->get('https://www.flickr.com/services/oembed?format=json&minwidth=1000&url=' . $this->flickrUrl)
                ->json();
        } catch (RequestException $e) {
            $this->logger->warning('Flickr extract failed for: ' . $this->flickrUrl, [
                'exception' => $e,
            ]);

            return '';
        }

        if (empty($data) || !isset($data['flickr_type'])) {
            return '';
        }

        return '<div>' .
            '<h2>' . $data['title'] . '</h2>' . '
            <p>By <a href="' . $data['author_url'] . '">' . $data['author_name'] . '</a></p>' .
            $data['html'] .
            '</div>';
    }
}
