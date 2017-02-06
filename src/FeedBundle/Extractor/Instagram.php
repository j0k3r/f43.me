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
     * Fetch content from Instagram about an url.
     *
     * @return array|false
     */
    private function retrieveData()
    {
        try {
            return $this->client
                ->get('https://api.instagram.com/oembed?url=' . $this->instagramUrl)
                ->json();
        } catch (RequestException $e) {
            $this->logger->warning('Instagram extract failed for: ' . $this->instagramUrl, [
                'exception' => $e,
            ]);

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->instagramUrl) {
            return '';
        }

        $data = $this->retrieveData();

        if (!is_array($data) || empty($data)) {
            return '';
        }

        return '<div><h2>' . $data['title'] . '</h2><p><img src="' . $data['thumbnail_url'] . '"></p>' . $data['html'] . '</div>';
    }

    /**
     * Return only the image instead of the whole html content.
     * Used in the Instagram converter.
     *
     * @return string
     */
    public function getImageOnly()
    {
        $data = $this->retrieveData();

        if (!is_array($data) || empty($data)) {
            return '';
        }

        return $data['thumbnail_url'];
    }
}
