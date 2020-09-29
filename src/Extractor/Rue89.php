<?php

namespace App\Extractor;

class Rue89 extends AbstractExtractor
{
    /** @var string */
    protected $rue89Id = null;
    /** @var bool */
    protected $isBlog = false;

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

        if (false === strpos((string) $host, 'rue89.nouvelobs.com')) {
            return false;
        }

        $this->isBlog = 0 === strpos((string) $path, '/blog/');

        preg_match('/\-([0-9]+)$/i', (string) $path, $matches);

        if (!isset($matches[1])) {
            return false;
        }

        $this->rue89Id = $matches[1];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent(): string
    {
        if (!$this->rue89Id) {
            return '';
        }

        $host = 'api.rue89.nouvelobs.com';
        if ($this->isBlog) {
            $host = 'api.blogs.rue89.nouvelobs.com';
        }

        try {
            $response = $this->client->get('http://' . $host . '/export/mobile2/node/' . $this->rue89Id . '/full');
            $data = $this->jsonDecode($response);
        } catch (\Exception $e) {
            $this->logger->warning('Rue89 extract failed for: ' . $this->rue89Id, [
                'exception' => $e,
            ]);

            return '';
        }

        if (!\is_array($data) || empty($data)) {
            return '';
        }

        return '<div><p>' . $data['node']['intro'] . '</p><p><img src="' . $data['node']['imgTabletteCarousel'] . '"></p>' . $data['node']['body'] . '</div>';
    }
}
