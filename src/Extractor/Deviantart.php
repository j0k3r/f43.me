<?php

namespace App\Extractor;

class Deviantart extends AbstractExtractor
{
    /** @var string */
    protected $deviantartUrl;

    public function match(string $url): bool
    {
        $host = parse_url($url, \PHP_URL_HOST);
        $path = parse_url($url, \PHP_URL_PATH);

        if (null === $host || null === $path) {
            return false;
        }

        // if it's a fav.me or sta.sh, we just that there is a kind of id after
        // and for a deviantart url, we check for an art url
        if (
            (\in_array($host, ['fav.me', 'sta.sh'], true) && preg_match('/\/([a-z0-9]+)/i', (string) $path, $matches))
            || (strpos((string) $host, 'deviantart.com') && preg_match('/\/art\/(.*)/i', (string) $path, $matches))) {
            $this->deviantartUrl = $url;

            return true;
        }

        return false;
    }

    /**
     * @see https://www.deviantart.com/developers/oembed
     */
    public function getContent(): string
    {
        if (!$this->deviantartUrl) {
            return '';
        }

        try {
            $response = $this->client->get('https://backend.deviantart.com/oembed?url=' . $this->deviantartUrl);
            $data = $this->jsonDecode($response);
        } catch (\Exception $e) {
            $this->logger->warning('Deviantart extract failed for: ' . $this->deviantartUrl, [
                'exception' => $e,
            ]);

            return '';
        }

        $content = '<div>
            <h2>' . $data['title'] . '</h2>
            <p>By <a href="' . $data['author_url'] . '">@' . $data['author_name'] . '</a></p>
            <p><i>' . $data['category'] . '</i></p>
            <img src="' . ($data['url'] ?? $data['thumbnail_url']) . '" />';

        if (isset($data['html'])) {
            $content .= '<p>' . $data['html'] . '</p>';
        }

        $content .= '</div>';

        return $content;
    }
}
