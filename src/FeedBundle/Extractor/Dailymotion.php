<?php

namespace Api43\FeedBundle\Extractor;

use GuzzleHttp\Exception\RequestException;

class Dailymotion extends AbstractExtractor
{
    protected $dailymotionUrl = null;

    /**
     * {@inheritdoc}
     */
    public function match($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);

        if (false === $host || false === $path) {
            return false;
        }

        if (!in_array($host, ['www.dailymotion.com', 'dailymotion.com', 'dai.ly'], true)) {
            return false;
        }

        $this->dailymotionUrl = $url;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->dailymotionUrl) {
            return '';
        }

        try {
            $data = $this->client
                ->get('http://www.dailymotion.com/services/oembed?format=json&url=' . $this->dailymotionUrl)
                ->json();
        } catch (RequestException $e) {
            $this->logger->warning('Dailymotion extract failed for: ' . $this->dailymotionUrl, [
                'exception' => $e,
            ]);

            return '';
        }

        if (!is_array($data) || empty($data)) {
            return '';
        }

        return '<div><h2>' . $data['title'] . '</h2><p><img src="' . $data['thumbnail_url'] . '"></p>' . $data['html'] . '</div>';
    }
}
