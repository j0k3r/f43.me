<?php

namespace App\Extractor;

class Youtube extends AbstractExtractor
{
    /** @var string */
    protected $youtubeUrl = null;

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

        if (!\in_array($host, ['www.youtube.com', 'youtube.com', 'youtu.be'], true)) {
            return false;
        }

        $this->youtubeUrl = $url;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent(): string
    {
        if (!$this->youtubeUrl) {
            return '';
        }

        try {
            $response = $this->client->get('https://www.youtube.com/oembed?format=json&url=' . $this->youtubeUrl);
            $data = $this->jsonDecode($response);
        } catch (\Exception $e) {
            $this->logger->warning('Youtube extract failed for: ' . $this->youtubeUrl, [
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
