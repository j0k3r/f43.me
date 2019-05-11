<?php

namespace AppBundle\Extractor;

use Http\Client\Exception\RequestException;

class Twitch extends AbstractExtractor
{
    protected $twitchCliendId;
    protected $twitchId = null;

    /**
     * @param string $twitchCliendId
     */
    public function __construct($twitchCliendId)
    {
        $this->twitchCliendId = $twitchCliendId;
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

        if (!\in_array($host, ['www.twitch.tv', 'twitch.tv'], true)) {
            return false;
        }

        // match twitch id
        preg_match('/v\/([0-9]+)/i', $path, $matches);

        if (!isset($matches[1])) {
            return false;
        }

        $this->twitchId = $matches[1];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->twitchId) {
            return '';
        }

        try {
            $response = $this->client->get('https://api.twitch.tv/kraken/videos/v' . $this->twitchId, ['headers' => ['Client-ID' => $this->twitchCliendId]]);
            $data = $this->jsonDecode($response);
        } catch (RequestException $e) {
            $this->logger->warning('Twitch extract failed for: ' . $this->twitchId, [
                'exception' => $e,
            ]);

            return '';
        }

        if (!\is_array($data) || empty($data)) {
            return '';
        }

        return '<div><h2>' . $data['title'] . '</h2><p>' . $data['description'] . '</p><p><img src="' . $data['preview'] . '"></p><iframe src="https://player.twitch.tv/?video=v' . $this->twitchId . '" frameborder="0" scrolling="no" height="378" width="620"></iframe></div>';
    }
}
