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

        if (!in_array($host, ['i.reddituploads.com', 'i.redd.it'])) {
            return false;
        }

        // match reddituploads id & redd.it id
        preg_match('/\/([a-z0-9]{32})|([a-z0-9]{12}\.)/', $path, $matches);

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
