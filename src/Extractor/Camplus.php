<?php

namespace App\Extractor;

class Camplus extends AbstractExtractor
{
    /** @var string */
    protected $camplusId = null;

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

        if (false === strpos((string) $host, 'campl.us')) {
            return false;
        }

        // find camplus photo id
        preg_match('/^\/([a-z0-9]+)$/i', (string) $path, $matches);

        if (2 !== \count($matches)) {
            return false;
        }

        $this->camplusId = $matches[1];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent(): string
    {
        if (!$this->camplusId) {
            return '';
        }

        try {
            $response = $this->client->get('http://campl.us/' . $this->camplusId . ':info');
            $data = $this->jsonDecode($response);
        } catch (\Exception $e) {
            $this->logger->warning('Camplus extract failed for: ' . $this->camplusId, [
                'exception' => $e,
            ]);

            return '';
        }

        $content = '<div>
            <h2>Photo from ' . $data['page']['tweet']['realname'] . '</h2>
            <p>By <a href="https://twitter.com/' . $data['page']['tweet']['username'] . '">@' . $data['page']['tweet']['username'] . '</a> – <a href="https://twitter.com/statuses/' . $data['page']['tweet']['id'] . '">related tweet</a></p>
            <p>' . $data['page']['tweet']['text'] . '</p>';

        foreach ($data['pictures'] as $value) {
            $content .= '<p><img src="' . $value['480px'] . '" /></p>';
        }

        $content .= '</div>';

        return $content;
    }
}
