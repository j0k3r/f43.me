<?php

namespace Api43\FeedBundle\Extractor;

class Reddituploads extends AbstractExtractor
{
    protected $reddituploadsUrl = null;

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

        if (false === strpos($host, 'i.reddituploads.com')) {
            return false;
        }

        // match reddituploads id
        preg_match('/\/([a-z0-9]{32})/', $path, $matches);

        if (!isset($matches[1])) {
            return false;
        }

        $this->reddituploadsUrl = $url;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->reddituploadsUrl) {
            return '';
        }

        return '<div><p><img src="'.$this->reddituploadsUrl.'"></p></div>';
    }
}
