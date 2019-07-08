<?php

namespace AppBundle\Extractor;

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

        if (null === $host || null === $path) {
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
            $response = $this->client->get('https://vimeo.com/api/oembed.xml?format=json&url=' . $this->vimeoUrl);
            $data = $this->jsonDecode($response);
        } catch (\Exception $e) {
            $this->logger->warning('Vimeo extract failed for: ' . $this->vimeoUrl, [
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
