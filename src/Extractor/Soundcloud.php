<?php

namespace App\Extractor;

class Soundcloud extends AbstractExtractor
{
    protected $soundCloundUrl = null;

    /**
     * {@inheritdoc}
     */
    public function match($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);

        if (null === $host || null === $path) {
            return false;
        }

        if (false === strpos($host, 'soundcloud.com')) {
            return false;
        }

        $this->soundCloundUrl = $url;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->soundCloundUrl) {
            return '';
        }

        try {
            $response = $this->client->get('http://soundcloud.com/oembed?format=json&url=' . $this->soundCloundUrl);
            $data = $this->jsonDecode($response);
        } catch (\Exception $e) {
            $this->logger->warning('Soundcloud extract failed for: ' . $this->soundCloundUrl, [
                'exception' => $e,
            ]);

            return '';
        }

        if (!\is_array($data) || empty($data)) {
            return '';
        }

        return '<div><h2>' . $data['title'] . '</h2><p>' . $data['description'] . '</p><p><img src="' . $data['thumbnail_url'] . '"></p>' . $data['html'] . '</div>';
    }
}
