<?php

namespace Api43\FeedBundle\Extractor;

use GuzzleHttp\Exception\RequestException;

class Vimeo extends AbstractExtractor
{
    protected $vimeoUrl = null;

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
            $data = $this->client
                ->get('https://vimeo.com/api/oembed.xml?format=json&url=' . $this->vimeoUrl)
                ->json();
        } catch (RequestException $e) {
            $this->logger->warning('Vimeo extract failed for: ' . $this->vimeoUrl, [
                'exception' => $e,
            ]);

            return '';
        }

        if (!is_array($data) || empty($data)) {
            return '';
        }

        return '<div><h2>' . $data['title'] . '</h2><p>' . $data['description'] . '</p><p><img src="' . $data['thumbnail_url'] . '"></p>' . $data['html'] . '</div>';
    }
}
