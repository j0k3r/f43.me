<?php

namespace Api43\FeedBundle\Extractor;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\RequestException;

class Vidme extends AbstractExtractor
{
    protected $guzzle;
    protected $vidmeUrl = null;

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

        if (0 !== strpos($host, 'vid.me')) {
            return false;
        }

        // match vidme id
        preg_match('/([a-z0-9]{4,})/i', $path, $matches);

        if (!isset($matches[1])) {
            return false;
        }

        $this->vidmeUrl = $url;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->vidmeUrl) {
            return '';
        }

        try {
            $data = $this->guzzle
                ->get('https://api.vid.me/videoByUrl?url='.$this->vidmeUrl)
                ->send()
                ->json();
        } catch (RequestException $e) {
            trigger_error('Vidme extract failed for "'.$this->vidmeUrl.'": '.$e->getMessage());

            return '';
        }

        if (!is_array($data) || empty($data) || !isset($data['video'])) {
            return '';
        }

        return '<div><h2>'.$data['video']['title'].'</h2><p><img src="'.$data['video']['thumbnail_url'].'"></p><iframe src="'.$data['video']['embed_url'].'"></iframe></div>';
    }
}
