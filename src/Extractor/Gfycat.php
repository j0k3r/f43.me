<?php

namespace App\Extractor;

class Gfycat extends AbstractExtractor
{
    /** @var string */
    protected $gfycatId;

    public function match(string $url): bool
    {
        $host = parse_url($url, \PHP_URL_HOST);
        $path = parse_url($url, \PHP_URL_PATH);

        if (null === $host || null === $path) {
            return false;
        }

        if (!str_contains((string) $host, 'gfycat.com')) {
            return false;
        }

        // remove unecessary stuff from url
        $path = str_replace('gifs/detail/', '', (string) $path);

        // match gfycat id with a minimum length or 4 to avoid i18n
        preg_match('/([a-zA-Z]{4,})/i', (string) $path, $matches);

        if (!isset($matches[1])) {
            return false;
        }

        $this->gfycatId = $matches[1];

        return true;
    }

    public function getContent(): string
    {
        if (!$this->gfycatId) {
            return '';
        }

        try {
            $response = $this->client->get('https://api.gfycat.com/v1/gfycats/' . $this->gfycatId);
            $data = $this->jsonDecode($response);
        } catch (\Exception $e) {
            $this->logger->warning('Gfycat extract failed for: ' . $this->gfycatId, [
                'exception' => $e,
            ]);

            return '';
        }

        if (!\is_array($data) || empty($data) || !isset($data['gfyItem'])) {
            return '';
        }

        return '<div><h2>' . $data['gfyItem']['title'] . '</h2><p><img src="' . $data['gfyItem']['posterUrl'] . '"></p></div>' .
            '<div><iframe src="https://gfycat.com/ifr/' . $this->gfycatId . '" frameborder="0" scrolling="no" allowfullscreen width="' . $data['gfyItem']['width'] . '" height="' . $data['gfyItem']['height'] . '"></iframe></div>';
    }
}
