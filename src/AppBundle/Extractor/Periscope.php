<?php

namespace AppBundle\Extractor;

class Periscope extends AbstractExtractor
{
    protected $periscopeId = null;

    /**
     * {@inheritdoc}
     */
    public function match($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);

        if (null === $host || null === $path) {
            return false;
        }

        if (!\in_array($host, ['www.pscp.tv', 'pscp.tv', 'www.periscope.tv', 'periscope.tv'], true)) {
            return false;
        }

        preg_match('/\/([a-z0-9]{13})/i', $path, $matches);

        if (!isset($matches[1])) {
            return false;
        }

        $this->periscopeId = $matches[1];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
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
