<?php

namespace Api43\FeedBundle\Extractor;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\RequestException;

class Instagram extends AbstractExtractor
{
    protected $guzzle;
    protected $instagramUrl = null;

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

        if (0 !== strpos($host, 'instagram.com') && 0 !== strpos($host, 'instagr.am')) {
            return false;
        }

        // instagram path always starts with a /p
        if (0 !== strpos($path, '/p')) {
            return false;
        }

        $this->instagramUrl = $url;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->instagramUrl) {
            return '';
        }

        try {
            $data = $this->guzzle
                ->get('http://api.instagram.com/oembed?url='.$this->instagramUrl)
                ->send()
                ->json();
        } catch (RequestException $e) {
            trigger_error('Instagram extract failed for "'.$this->instagramUrl.'": '.$e->getMessage());

            return '';
        }

        if (!is_array($data) || empty($data)) {
            return '';
        }

        return '<div><h2>'.$data['title'].'</h2><p><img src="'.$data['thumbnail_url'].'"></p>'.$data['html'].'</div>';
    }
}
