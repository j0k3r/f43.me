<?php

namespace Api43\FeedBundle\Extractor;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Github extends AbstractExtractor
{
    protected $guzzle;
    protected $githubRepo;

    /**
     * @param Client $guzzle
     */
    public function __construct(Client $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    /**
     * {@inheritdoc}
     */
    public function match($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);

        if (false === $path || false === $host) {
            return false;
        }

        if (false === strpos($host, 'github.com')) {
            return false;
        }

        // find github user and project
        preg_match('/^\/([\w\d\.-]+)\/([\w\d\.-]+)\/?$/i', $path, $matches);

        if (3 !== count($matches)) {
            return false;
        }

        $this->githubRepo = $matches[1].'/'.$matches[2];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->githubRepo) {
            return '';
        }

        try {
            return $this->guzzle
                ->get(
                    'https://api.github.com/repos/'.$this->githubRepo.'/readme',
                    array(
                        'Accept' => 'application/vnd.github.v3.html',
                        'User-Agent' => 'f43.me / Github Extractor',
                    )
                )
                ->getBody(true);
        } catch (RequestException $e) {
            // Github will return a 404 if no readme are found
            return '';
        }
    }
}
