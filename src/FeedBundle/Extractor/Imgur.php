<?php

namespace Api43\FeedBundle\Extractor;

use Imgur\Client;

class Imgur extends AbstractExtractor
{
    protected $imgurClient;
    protected $hash = null;
    protected $type = null;

    /**
     * @param Client $imgurClient
     */
    public function __construct(Client $imgurClient)
    {
        $this->imgurClient = $imgurClient;
    }

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

        // some gallery got an extra query like ?gallery to change the display, we don't want it
        $query = parse_url($url, PHP_URL_QUERY);
        if ($query) {
            $url = str_replace('?'.$query, '', $url);
        }

        // find the hash and the type (gallery or single image)
        // from https://github.com/extesy/hoverzoom/blob/master/plugins/imgur_a.js
        preg_match('/(?:\/(a|gallery|signin))?\/([^\W_]{5,8})(?:\/|\.[a-zA-Z]+|#([^\W_]{5,8}|\d+))?(\/new|\/all|\?.*)?$/', $url, $matches);

        if ((0 !== strpos($host, 'imgur') && 0 !== strpos($host, 'i.imgur')) || !isset($matches[2])) {
            return false;
        }

        $this->hash = $matches[2];
        $this->type = $matches[1];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->hash && !$this->type) {
            return '';
        }

        $images = [];
        $content = '';

        try {
            $albumOrImage = $this->imgurClient->api('albumOrImage')->find($this->hash);
        } catch (\Exception $e) {
            $this->logger->warning('Imgur extract failed for: '.$this->hash, [
                'exception' => $e,
            ]);

            return '';
        }

        $images[] = $albumOrImage;
        if (isset($albumOrImage['images'])) {
            $content = '<h2>'.$albumOrImage['title'].'</h2><p>'.$albumOrImage['description'].'</p>';
            $images = $albumOrImage['images'];
        }

        foreach ($images as $image) {
            $info = '<p>'.trim($image['title']);
            $info .= $image['description'] ? ' – '.trim($image['description']) : '';
            $info .= '</p>';

            if (!$image['title'] && !$image['description']) {
                $info = '';
            }

            $content .= '<div>'.$info.'<img src="'.$image['link'].'" /></div>';
        }

        return $content;
    }
}
