<?php

namespace App\Extractor;

use Imgur\Client;

class Imgur extends AbstractExtractor
{
    /** @var Client */
    protected $imgurClient;
    /** @var string */
    protected $hash = null;
    /** @var string */
    protected $type = null;

    public function __construct(Client $imgurClient)
    {
        $this->imgurClient = $imgurClient;
    }

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

        // some gallery got an extra query like ?gallery to change the display, we don't want it
        $query = parse_url($url, \PHP_URL_QUERY);
        if ($query) {
            $url = str_replace('?' . $query, '', $url);
        }

        // find the hash and the type (gallery or single image)
        // from https://github.com/extesy/hoverzoom/blob/master/plugins/imgur_a.js
        preg_match('/(?:\/(a|gallery|signin))?\/([^\W_]{5,8})(?:\/|\.[a-zA-Z]+|#([^\W_]{5,8}|\d+))?(\/new|\/all|\?.*)?$/', $url, $matches);

        if ((0 !== strpos((string) $host, 'imgur') && 0 !== strpos((string) $host, 'i.imgur')) || !isset($matches[2])) {
            return false;
        }

        $this->hash = $matches[2];
        $this->type = $matches[1];

        // remove non-media
        if (\in_array($this->hash, ['imgur', 'forum', 'stats', 'signin', 'upgrade'], true)) {
            return false;
        }

        if ('signin' === $this->type) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent(): string
    {
        if (!$this->hash && !$this->type) {
            return '';
        }

        $images = [];
        $content = '';
        $albumOrImage = null;

        if (\in_array($this->type, ['a', 'gallery'], true)) {
            try {
                /** @var \Imgur\Api\Album */
                $album = $this->imgurClient->api('album');
                $albumOrImage = $album->album($this->hash);
            } catch (\Exception $e) {
                $this->logger->warning('Imgur extract failed with "album" for: ' . $this->hash, [
                    'exception' => $e,
                ]);
            }
        }

        if (null === $albumOrImage) {
            try {
                /** @var \Imgur\Api\Image */
                $image = $this->imgurClient->api('image');
                $albumOrImage = $image->image($this->hash);
            } catch (\Exception $e) {
                $this->logger->warning('Imgur extract failed with "image" for: ' . $this->hash, [
                    'exception' => $e,
                ]);
            }
        }

        if (null === $albumOrImage) {
            return '';
        }

        $images[] = $albumOrImage;
        if (isset($albumOrImage['images'])) {
            $content = '<h2>' . $albumOrImage['title'] . '</h2><p>' . $albumOrImage['description'] . '</p>';
            $images = $albumOrImage['images'];
        }

        foreach ($images as $image) {
            $info = '<p>' . trim($image['title']);
            $info .= $image['description'] ? ' – ' . trim($image['description']) : '';
            $info .= '</p>';

            if (!$image['title'] && !$image['description']) {
                $info = '';
            }

            // some gifv hasn't a gif alternative
            if (strpos($image['link'], '.mp4')) {
                $content .= '<video width="' . $image['width'] . '" height="' . $image['height'] . '" controls="controls"><source src="' . $image['link'] . '" type="video/mp4" /></video>';
            } else {
                $content .= '<div>' . $info . '<img src="' . $image['link'] . '" /></div>';
            }
        }

        return $content;
    }
}
