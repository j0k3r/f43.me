<?php

namespace App\Extractor;

class Streamable extends AbstractExtractor
{
    /** @var string */
    protected $streamableUrl = null;

    /**
     * {@inheritdoc}
     */
    public function match(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);

        if (null === $host || null === $path) {
            return false;
        }

        if (!\in_array($host, ['www.streamable.com', 'streamable.com'], true)) {
            return false;
        }

        $this->streamableUrl = $url;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent(): string
    {
        if (!$this->streamableUrl) {
            return '';
        }

        try {
            $response = $this->client->get('https://api.streamable.com/oembed.json?url=' . $this->streamableUrl);
            $data = $this->jsonDecode($response);
        } catch (\Exception $e) {
            $this->logger->warning('Streamable extract failed for: ' . $this->streamableUrl, [
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
