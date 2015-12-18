<?php

namespace Api43\FeedBundle\Extractor;

use GuzzleHttp\Exception\RequestException;

class Gfycat extends AbstractExtractor
{
    protected $gfycatId = null;

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

        if (false === strpos($host, 'gfycat.com')) {
            return false;
        }

        // match gfycat id
        preg_match('/([a-zA-Z]+)/i', $path, $matches);

        if (!isset($matches[1])) {
            return false;
        }

        $this->gfycatId = $matches[1];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->gfycatId) {
            return '';
        }

        try {
            $data = $this->client
                ->get('http://gfycat.com/cajax/get/'.$this->gfycatId)
                ->json();
        } catch (RequestException $e) {
            $this->logger->warning('Gfycat extract failed for: '.$this->gfycatId, [
                'exception' => $e,
            ]);

            return '';
        }

        if (!is_array($data) || empty($data) || !isset($data['gfyItem'])) {
            return '';
        }

        return '<div><h2>'.$data['gfyItem']['title'].'</h2><p><img src="'.$data['gfyItem']['gifUrl'].'"></p></div>';
    }
}
