<?php

namespace App\Extractor;

class Vine extends AbstractExtractor
{
    protected $vineId = null;

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

        if (false === strpos($host, 'vine.co')) {
            return false;
        }

        // find vine id
        preg_match('/^\/v\/([a-zA-Z0-9]+)/', $path, $matches);

        if (!isset($matches[1])) {
            return false;
        }

        $this->vineId = $matches[1];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->vineId) {
            return '';
        }

        try {
            $response = $this->client->get('https://vine.co/oembed.json?id=' . $this->vineId);
            $data = $this->jsonDecode($response);
        } catch (\Exception $e) {
            $this->logger->warning('Vine extract failed for: ' . $this->vineId, [
                'exception' => $e,
            ]);

            return '';
        }

        if (!\is_array($data) || empty($data)) {
            return '';
        }

        return '<div><h2>' . $data['title'] . '</h2><p><img src="' . $data['thumbnail_url'] . '"></p>' . $data['html'] . '</div>';
    }
}
