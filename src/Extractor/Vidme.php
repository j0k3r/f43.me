<?php

namespace App\Extractor;

class Vidme extends AbstractExtractor
{
    /** @var string */
    protected $vidmeUrl;

    public function match(string $url): bool
    {
        $host = parse_url($url, \PHP_URL_HOST);
        $path = parse_url($url, \PHP_URL_PATH);

        if (null === $host || null === $path) {
            return false;
        }

        if (!str_starts_with((string) $host, 'vid.me')) {
            return false;
        }

        // match vidme id
        preg_match('/([a-z0-9]{4,})/i', (string) $path, $matches);

        if (!isset($matches[1])) {
            return false;
        }

        $this->vidmeUrl = $url;

        return true;
    }

    public function getContent(): string
    {
        if (!$this->vidmeUrl) {
            return '';
        }

        try {
            $response = $this->client->get('https://api.vid.me/videoByUrl?url=' . $this->vidmeUrl);
            $data = $this->jsonDecode($response);
        } catch (\Exception $e) {
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
