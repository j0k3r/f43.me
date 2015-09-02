<?php

namespace Api43\FeedBundle\Extractor;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Camplus extends AbstractExtractor
{
    protected $guzzle;
    protected $camplusId = null;

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

        if (false === strpos($host, 'campl.us')) {
            return false;
        }

        // find camplus photo id
        preg_match('/^\/([a-z0-9]+)$/i', $path, $matches);

        if (2 !== count($matches)) {
            return false;
        }

        $this->camplusId = $matches[1];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->camplusId) {
            return '';
        }

        try {
            $data = $this->guzzle
                ->get('http://campl.us/'.$this->camplusId.':info')
                ->json();
        } catch (RequestException $e) {
            trigger_error('Camplus extract failed for "'.$this->camplusId.'": '.$e->getMessage());

            return false;
        }

        $content = '<div>
            <h2>Photo from '.$data['page']['tweet']['realname'].'</h2>
            <p>By <a href="https://twitter.com/'.$data['page']['tweet']['username'].'">@'.$data['page']['tweet']['username'].'</a> – <a href="https://twitter.com/statuses/'.$data['page']['tweet']['id'].'">related tweet</a></p>
            <p>'.$data['page']['tweet']['text'].'</p>';

        foreach ($data['pictures'] as $value) {
            $content .= '<p><img src="'.$value['480px'].'" /></p>';
        }

        $content .= '</div>';

        return $content;
    }
}
