<?php

namespace App\Extractor;

class Tumblr extends AbstractExtractor
{
    /** @var string */
    protected $tumblrApiKey;
    /** @var string */
    protected $tumblrId = null;
    /** @var string */
    protected $tumblrHost = null;

    public function __construct(string $tumblrApiKey)
    {
        $this->tumblrApiKey = $tumblrApiKey;
    }

    /**
     * {@inheritdoc}
     */
    public function match(string $url): bool
    {
        $host = parse_url($url, \PHP_URL_HOST);
        $path = parse_url($url, \PHP_URL_PATH);

        if (null === $host || null === $path) {
            return false;
        }

        // find tumblr post id
        preg_match('/post\/([0-9]+)/', (string) $path, $matches);

        if (!isset($matches[1])) {
            return false;
        }

        try {
            // retrieve the tumblr user to validate that's a tumblr post
            $tumblrUser = $this->client
                ->get($url)
                ->getHeaderLine('X-Tumblr-User');
        } catch (\Exception $e) {
            $this->logger->warning('Tumblr extract failed for: ' . $url, [
                'exception' => $e,
            ]);

            return false;
        }

        if (!$tumblrUser) {
            return false;
        }

        $this->tumblrId = $matches[1];
        $this->tumblrHost = (string) $host;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent(): string
    {
        if (!$this->tumblrId && !$this->tumblrHost) {
            return '';
        }

        try {
            $response = $this->client->get('https://api.tumblr.com/v2/blog/' . $this->tumblrHost . '/posts/text?api_key=' . $this->tumblrApiKey . '&id=' . $this->tumblrId);
            $data = $this->jsonDecode($response);
        } catch (\Exception $e) {
            $this->logger->warning('Tumblr extract failed for: ' . $this->tumblrId . ' & ' . $this->tumblrHost, [
                'exception' => $e,
            ]);

            return '';
        }

        if (!isset($data['response']['posts'][0]['body'])) {
            return '';
        }

        return $data['response']['posts'][0]['body'];
    }
}
