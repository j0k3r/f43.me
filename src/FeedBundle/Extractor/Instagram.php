<?php

namespace Api43\FeedBundle\Extractor;

use GuzzleHttp\Exception\RequestException;

class Instagram extends AbstractExtractor
{
    protected $instagramUrl = null;

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

        if (!in_array($host, ['instagr.am', 'instagram.com', 'www.instagram.com'], true)) {
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
            $data = $this->client
                ->get('https://api.instagram.com/oembed?url=' . $this->instagramUrl)
                ->json();
        } catch (RequestException $e) {
            $this->logger->warning('Instagram extract failed for: ' . $this->instagramUrl, [
                'exception' => $e,
            ]);

            return '';
        }

        if (!is_array($data) || empty($data)) {
            return '';
        }

        return '<div><h2>' . $data['title'] . '</h2><p><img src="' . $data['thumbnail_url'] . '"></p>' . $data['html'] . '</div>';
    }
}
