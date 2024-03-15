<?php

namespace App\Extractor;

class RedditPost extends AbstractExtractor
{
    /** @var array */
    protected $redditPostData;

    public function match(string $url): bool
    {
        $host = parse_url($url, \PHP_URL_HOST);
        $path = parse_url($url, \PHP_URL_PATH);

        if (null === $host || null === $path) {
            return false;
        }

        if (!\in_array($host, ['reddit.com', 'www.reddit.com'], true)) {
            return false;
        }

        $url = 'https://' . $host . $path . '/.json';
        $url = str_replace('//.json', '/.json', $url);

        try {
            $response = $this->client->get($url);
            $data = $this->jsonDecode($response);
        } catch (\Exception $e) {
            return false;
        }

        // we only match reddit post
        if (!isset($data[0]['data']['children'][0]['data'])
            || true !== $data[0]['data']['children'][0]['data']['is_self']) {
            return false;
        }

        $this->redditPostData = $data[0]['data']['children'][0]['data'];

        return true;
    }

    public function getContent(): string
    {
        if (!$this->redditPostData) {
            return '';
        }

        return '<div><h2>' . $this->redditPostData['title'] . '</h2>' .
            '<ul><li>Score: ' . $this->redditPostData['score'] . '</li><li>Comments: ' . $this->redditPostData['num_comments'] . '</li><li>Flair: ' . $this->redditPostData['link_flair_text'] . '</li><li>Author: ' . $this->redditPostData['author'] . '</li></ul>' .
            '</div>' . htmlspecialchars_decode($this->redditPostData['selftext_html']);
    }
}
