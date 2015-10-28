<?php

namespace Api43\FeedBundle\Extractor;

use GuzzleHttp\Exception\RequestException;

class Rue89 extends AbstractExtractor
{
    protected $rue89Id = null;

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

        if (false === strpos($host, 'rue89.nouvelobs.com')) {
            return false;
        }

        preg_match('/\-([0-9]+)$/i', $path, $matches);

        if (!isset($matches[1])) {
            return false;
        }

        $this->rue89Id = $matches[1];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->rue89Id) {
            return '';
        }

        try {
            $data = $this->client
                ->get('http://api.rue89.nouvelobs.com/export/mobile2/node/'.$this->rue89Id.'/full')
                ->json();
        } catch (RequestException $e) {
            $this->logger->warning('Rue89 extract failed for: '.$this->rue89Id, array(
                'exception' => $e,
            ));

            return '';
        }

        if (!is_array($data) || empty($data)) {
            return '';
        }

        return '<div><p>'.$data['node']['intro'].'</p><p><img src="'.$data['node']['imgTabletteCarousel'].'"></p>'.$data['node']['body'].'</div>';
    }
}
