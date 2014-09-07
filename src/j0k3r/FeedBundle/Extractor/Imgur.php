<?php

namespace j0k3r\FeedBundle\Extractor;

use Imgur\Client;
use Guzzle\Common\Exception\RuntimeException;

class Imgur extends AbstractExtractor
{
    protected $imgurClient;
    protected $hash = null;
    protected $type = null;

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

        // find the hash and the type (gallery or single image)
        preg_match('/(?:\/(a|gallery|signin))?\/([^\W_]{5,7})(?:\/|\.[a-zA-Z]+|#([^\W_]{5,7}|\d+))?$/', $url, $matches);

        if (0 !== strpos($host, 'imgur') || false !== strpos($path, '.') || !isset($matches[2])) {
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
            return false;
        }

        $images = array();
        $content = '';

        try {
            switch ($this->type) {
                case 'a':
                case 'gallery':
                    $album = $this->imgurClient->api('gallery')->album($this->hash);
                    $images = $album->getImages();

                    $content = '<h2>'.$album->getTitle().'</h2><p>'.$album->getDescription().'</p>';
                    break;

                default:
                    $images[] = $this->imgurClient->api('image')->image($this->hash);
                    break;
            }
        } catch (RuntimeException $e) {
            return false;
        }

        foreach ($images as $image) {
            $info = '<p>'.$image->getTitle().' – '.$image->getDescription().'</p>';

            if (!$image->getTitle() && !$image->getDescription()) {
                $info = '';
            }

            $content .= '<div>'.$info.'<img src="'.$image->getLink().'" /></div>';
        }

        return $content;
    }
}
