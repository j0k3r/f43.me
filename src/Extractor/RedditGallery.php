<?php

namespace App\Extractor;

class RedditGallery extends AbstractExtractor
{
    /** @var array */
    protected $redditGalleryData;

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
        $url = str_replace('/gallery/', '/comments/', $url);

        try {
            $response = $this->client->get($url);
            $data = $this->jsonDecode($response);
        } catch (\Exception $e) {
            return false;
        }

        // we only match reddit post
        if (!isset($data[0]['data']['children'][0]['data'])
            || true !== $data[0]['data']['children'][0]['data']['is_gallery']) {
            return false;
        }

        $this->redditGalleryData = $data[0]['data']['children'][0]['data'];

        return true;
    }

    public function getContent(): string
    {
        if (!$this->redditGalleryData) {
            return '';
        }

        $content = '<div><h2>' . $this->redditGalleryData['title'] . '</h2>' .
            '<ul><li>Score: ' . $this->redditGalleryData['score'] . '</li><li>Comments: ' . $this->redditGalleryData['num_comments'] . '</li><li>Flair: ' . $this->redditGalleryData['link_flair_text'] . '</li><li>Author: ' . $this->redditGalleryData['author'] . '</li></ul>';

        foreach ($this->redditGalleryData['gallery_data']['items'] as $item) {
            if ($item['caption']??false) {
                $content .= '<p>' . $item['caption'] . '</p>';
                $content .= '<p><img src="' . $this->redditGalleryData['media_metadata'][$item['media_id']]['s']['u'] . '" /></p>';
            }
        }

        $content .= '</div>';

        return $content;
    }
}
