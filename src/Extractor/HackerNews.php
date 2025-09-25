<?php

namespace App\Extractor;

class HackerNews extends AbstractExtractor
{
    /** @var string */
    protected $text;

    public function match(string $url): bool
    {
        $host = parse_url($url, \PHP_URL_HOST);
        $query = parse_url($url, \PHP_URL_QUERY);

        if (null === $host || null === $query) {
            return false;
        }

        if (!str_starts_with((string) $host, 'news.ycombinator.com')) {
            return false;
        }

        // match HN id
        preg_match('/id\=([0-9]+)/i', (string) $query, $matches);

        if (!isset($matches[1])) {
            return false;
        }

        try {
            $response = $this->client->get('https://hacker-news.firebaseio.com/v0/item/' . $matches[1] . '.json');
            $data = $this->jsonDecode($response);
        } catch (\Exception) {
            return false;
        }

        if (\in_array($data['type'], ['comment', 'pollopt'], true)
            || !isset($data['text'])
            || '' === trim($data['text'])) {
            return false;
        }

        $this->text = $data['text'];

        return true;
    }

    public function getContent(): string
    {
        if (!$this->text) {
            return '';
        }

        return '<p>' . $this->text . '</p>';
    }
}
