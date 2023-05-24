<?php

namespace App\Extractor;

class Periscope extends AbstractExtractor
{
    /** @var string */
    protected $periscopeId;

    public function match(string $url): bool
    {
        $host = parse_url($url, \PHP_URL_HOST);
        $path = parse_url($url, \PHP_URL_PATH);

        if (null === $host || null === $path) {
            return false;
        }

        if (!\in_array($host, ['www.pscp.tv', 'pscp.tv', 'www.periscope.tv', 'periscope.tv'], true)) {
            return false;
        }

        preg_match('/\/([a-z0-9]{13})/i', (string) $path, $matches);

        if (!isset($matches[1])) {
            return false;
        }

        $this->periscopeId = $matches[1];

        return true;
    }

    public function getContent(): string
    {
        if (!$this->periscopeId) {
            return '';
        }

        try {
            $response = $this->client->get('https://api.periscope.tv/api/v2/accessVideoPublic?broadcast_id=' . $this->periscopeId);
            $data = $this->jsonDecode($response);
        } catch (\Exception $e) {
            $this->logger->warning('Periscope extract failed for: ' . $this->periscopeId, [
                'exception' => $e,
            ]);

            return '';
        }

        if (!\is_array($data) || empty($data)) {
            return '';
        }

        return '<div><h2>' . $data['broadcast']['status'] . '</h2><p>Broadcast available on <a href="' . $data['share_url'] . '">Periscope</a>.</p><p><img src="' . $data['broadcast']['image_url'] . '"></p></div>';
    }
}
