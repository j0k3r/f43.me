<?php

namespace Api43\FeedBundle\Extractor;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Deviantart extends AbstractExtractor
{
    protected $guzzle;
    protected $deviantartUrl = null;

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

        if (false === $host || false === $path) {
            return false;
        }

        // if it's a fav.me or sta.sh, we just that there is a kind of id after
        // and for a deviantart url, we check for an art url
        if (
            (in_array($host, array('fav.me', 'sta.sh')) && preg_match('/\/([a-z0-9]+)/i', $path, $matches))
            || (strpos($host, 'deviantart.com') && preg_match('/\/art\/(.*)/i', $path, $matches))) {
            $this->deviantartUrl = $url;

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @see https://www.deviantart.com/developers/oembed
     */
    public function getContent()
    {
        if (!$this->deviantartUrl) {
            return false;
        }

        try {
            $data = $this->guzzle
                ->get('http://backend.deviantart.com/oembed?url='.$this->deviantartUrl)
                ->json();
        } catch (RequestException $e) {
            trigger_error('Deviantart extract failed for "'.$this->deviantartUrl.'": '.$e->getMessage());

            return false;
        }

        $content = '<div>
            <h2>'.$data['title'].'</h2>
            <p>By <a href="'.$data['author_url'].'">@'.$data['author_name'].'</a></p>
            <p><i>'.$data['category'].'</i></p>
            <img src="'.(isset($data['url']) ? $data['url'] : $data['thumbnail_url']).'" />';

        if (isset($data['html'])) {
            $content .= '<p>'.$data['html'].'</p>';
        }

        $content .= '</div>';

        return $content;
    }
}
