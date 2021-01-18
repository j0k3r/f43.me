<?php

namespace App\Extractor;

class Dailymotion extends AbstractExtractor
{
    /** @var string */
    protected $dailymotionUrl = null;

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

        if (!\in_array($host, ['www.dailymotion.com', 'dailymotion.com', 'dai.ly'], true)) {
            return false;
        }

        $this->dailymotionUrl = $url;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent(): string
    {
        if (!$this->dailymotionUrl) {
            return '';
        }

        try {
            $response = $this->client->get('https://www.dailymotion.com/services/oembed?format=json&url=' . $this->dailymotionUrl);
            $data = $this->jsonDecode($response);
        } catch (\Exception $e) {
            $this->logger->warning('Dailymotion extract failed for: ' . $this->dailymotionUrl, [
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
