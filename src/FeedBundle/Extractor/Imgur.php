<?php

namespace Api43\FeedBundle\Extractor;

use Imgur\Client;
use Guzzle\Common\Exception\RuntimeException;

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
        preg_match('/(?:\/(a|gallery|signin))?\/([^\W_]{5,7})(?:\/(new)?|\.[a-zA-Z]+|#([^\W_]{5,7}|\d+))?$/', $url, $matches);

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
            switch ($this->type) {
                case 'a':
                    $album = $this->imgurClient->api('album')->album($this->hash);
                    $images = $album->getImages();

                    $content = '<h2>'.$album->getTitle().'</h2><p>'.$album->getDescription().'</p>';
                    break;

                case 'gallery':
                    $images[] = $this->imgurClient->api('gallery')->image($this->hash);
                    break;

                default:
                    $images[] = $this->imgurClient->api('image')->image($this->hash);
                    break;
            }
        } catch (RuntimeException $e) {
            $this->logger->warning('Imgur extract failed for: '.$this->hash, [
                'exception' => $e,
            ]);

            return '';
        }

        foreach ($images as $image) {
            $info = '<p>'.$image->getTitle();
            $info .= $image->getDescription() ? ' – '.$image->getDescription() : '';
            $info .= '</p>';

            if (!$image->getTitle() && !$image->getDescription()) {
                $info = '';
            }

            $content .= '<div>'.$info.'<img src="'.$image->getLink().'" /></div>';
        }

        return $content;
    }
}
