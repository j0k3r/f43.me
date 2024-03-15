<?php

namespace App\Extractor;

class Spotify extends AbstractExtractor
{
    /** @var string */
    protected $spotifyUrl;

    public function match(string $url): bool
    {
        $host = parse_url($url, \PHP_URL_HOST);
        $path = parse_url($url, \PHP_URL_PATH);

        if (null === $host || null === $path) {
            return false;
        }

        if (!\in_array($host, ['open.spotify.com', 'play.spotify.com'], true)) {
            return false;
        }

        $this->spotifyUrl = $url;

        return true;
    }

    public function getContent(): string
    {
        if (!$this->spotifyUrl) {
            return '';
        }

        try {
            $response = $this->client->get('https://embed.spotify.com/oembed/?format=json&url=' . $this->spotifyUrl);
            $data = $this->jsonDecode($response);
        } catch (\Exception $e) {
            $this->logger->warning('Spotify extract failed for: ' . $this->spotifyUrl, [
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
