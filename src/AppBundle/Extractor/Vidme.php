<?php

namespace AppBundle\Extractor;

use Http\Client\Exception\RequestException;

class Vidme extends AbstractExtractor
{
    protected $vidmeUrl = null;

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
            $response = $this->client->get('https://api.vid.me/videoByUrl?url=' . $this->vidmeUrl);
            $data = $this->jsonDecode($response);
        } catch (RequestException $e) {
            $this->logger->warning('Vidme extract failed for: ' . $this->vidmeUrl, [
                'exception' => $e,
            ]);

            return '';
        }

        if (!\is_array($data) || empty($data) || !isset($data['video'])) {
            return '';
        }

        return '<div><h2>' . $data['video']['title'] . '</h2><p><img src="' . $data['video']['thumbnail_url'] . '"></p><iframe src="' . $data['video']['embed_url'] . '"></iframe></div>';
    }
}
