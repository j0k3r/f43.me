<?php

namespace App\Extractor;

class RedditVideo extends AbstractExtractor
{
    /** @var array */
    protected $redditVideoData = null;

    /**
     * {@inheritdoc}
     */
    public function match(string $url): bool
    {
        $host = parse_url($url, \PHP_URL_HOST);
        $path = parse_url($url, \PHP_URL_PATH);

        if (null === $host || null === $path) {
            return false;
        }

        // usually happen when fetching from the debug page
        if (\in_array($host, ['reddit.com', 'www.reddit.com'], true)) {
            $jsonUrl = 'https://' . $host . $path . '/.json';
        }

        // usually happen when fetching from the RSS feed
        if (\in_array($host, ['v.redd.it'], true)) {
            $jsonUrl = 'https://www.reddit.com/video/' . $path . '/.json';
        }

        if (!isset($jsonUrl)) {
            return false;
        }

        $jsonUrl = str_replace('//', '/', $jsonUrl);

        try {
            $response = $this->client->get($jsonUrl);
            $data = $this->jsonDecode($response);
        } catch (\Exception $e) {
            return false;
        }

        // we only match reddit video
        if (!isset($data[0]['data']['children'][0]['data'])
            || 'v.redd.it' !== $data[0]['data']['children'][0]['data']['domain']) {
            return false;
        }

        $this->redditVideoData = $data[0]['data']['children'][0]['data'];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent(): string
    {
        if (!$this->redditVideoData) {
            return '';
        }

        $thumbnail = $this->redditVideoData['thumbnail'];
        if (!empty($this->redditVideoData['preview']['images'][0]['source']['url'])) {
            $thumbnail = $this->redditVideoData['preview']['images'][0]['source']['url'];
        }

        return '<div><h2>' . $this->redditVideoData['title'] . '</h2>' .
            '<ul><li>Score: ' . $this->redditVideoData['score'] . '</li><li>Comments: ' . $this->redditVideoData['num_comments'] . '</li><li>Flair: ' . $this->redditVideoData['link_flair_text'] . '</li></ul>' .
            '<p><img src="' . $thumbnail . '"></p></div>' .
            '<iframe src="' . $this->redditVideoData['media']['reddit_video']['fallback_url'] . '" frameborder="0" scrolling="no" width="' . $this->redditVideoData['media']['reddit_video']['width'] . '" height="' . $this->redditVideoData['media']['reddit_video']['height'] . '" allowfullscreen></iframe>';
    }
}
