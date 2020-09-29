<?php

namespace App\Extractor;

class Instagram extends AbstractExtractor
{
    /** @var string */
    protected $instagramUrl = null;

    /**
     * {@inheritdoc}
     */
    public function match(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);

        if (null === $host || null === $path) {
            return false;
        }

        if (!\in_array($host, ['instagr.am', 'instagram.com', 'www.instagram.com'], true)) {
            return false;
        }

        // instagram path always starts with a /p
        if (0 !== strpos((string) $path, '/p')) {
            return false;
        }

        $this->instagramUrl = $url;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent(): string
    {
        if (!$this->instagramUrl) {
            return '';
        }

        $data = $this->retrieveData();

        if (!\is_array($data) || empty($data)) {
            return '';
        }

        return '<div class="f43me-instagram-extracted"><h2>' . $data['title'] . '</h2><p><img src="' . $data['thumbnail_url'] . '"></p>' . $data['html'] . '</div>';
    }

    /**
     * Return only the image instead of the whole html content.
     * Used in the Instagram converter.
     */
    public function getImageOnly(): string
    {
        $data = $this->retrieveData();

        if (!\is_array($data) || empty($data)) {
            return '';
        }

        return $data['thumbnail_url'];
    }

    /**
     * Fetch content from Instagram about an url.
     *
     * @return array|false
     */
    private function retrieveData()
    {
        try {
            $response = $this->client->get('https://api.instagram.com/oembed?url=' . $this->instagramUrl);

            return $this->jsonDecode($response);
        } catch (\Exception $e) {
            $this->logger->warning('Instagram extract failed for: ' . $this->instagramUrl, [
                'exception' => $e,
            ]);

            return false;
        }
    }
}
