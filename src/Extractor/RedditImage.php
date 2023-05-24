<?php

namespace App\Extractor;

class RedditImage extends AbstractExtractor
{
    /** @var string */
    protected $redditImageUrl;

    public function match(string $url): bool
    {
        $host = parse_url($url, \PHP_URL_HOST);
        $path = parse_url($url, \PHP_URL_PATH);

        if (null === $host || null === $path) {
            return false;
        }

        if (!\in_array($host, ['i.reddituploads.com', 'i.redd.it'], true)) {
            return false;
        }

        // match i.reddituploads id & i.redd.it id
        preg_match('/\/([a-z0-9]{32})|([a-z0-9]{12}\.)/', (string) $path, $matches);

        if (!isset($matches[1])) {
            return false;
        }

        $this->redditImageUrl = $url;

        return true;
    }

    public function getContent(): string
    {
        if (!$this->redditImageUrl) {
            return '';
        }

        return '<div><p><img src="' . $this->redditImageUrl . '"></p></div>';
    }
}
