<?php

namespace App\Extractor;

use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\Authentication\BasicAuth;

class Github extends AbstractExtractor
{
    protected $githubClientId;
    protected $githubClientSecret;
    protected $githubRepo;
    protected $pullNumber;
    protected $issueNumber;

    /**
     * @param string $githubClientId
     * @param string $githubClientSecret
     */
    public function __construct($githubClientId, $githubClientSecret)
    {
        $this->githubClientId = $githubClientId;
        $this->githubClientSecret = $githubClientSecret;
    }

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

        if (false === strpos($host, 'github.com')) {
            return false;
        }

        // find github user and project only
        preg_match('/^\/([\w\d\.-]+)\/([\w\d\.-]+)\/?$/i', $path, $matches);

        if (3 === \count($matches)) {
            $this->githubRepo = $matches[1] . '/' . $matches[2];

            return true;
        }

        // find pull request or issue
        preg_match('/^\/([\w\d\.-]+)\/([\w\d\.-]+)\/(pull|issues)\/([0-9]+)/i', $path, $matches);

        if (5 === \count($matches)) {
            $this->githubRepo = $matches[1] . '/' . $matches[2];

            if ('pull' === $matches[3]) {
                $this->pullNumber = $matches[4];
            } elseif ('issues' === $matches[3]) {
                $this->issueNumber = $matches[4];
            }

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->githubRepo) {
            return '';
        }

        $authentication = new BasicAuth($this->githubClientId, $this->githubClientSecret);
        $messageFactory = MessageFactoryDiscovery::find();

        if (null !== $this->pullNumber) {
            try {
                $request = $messageFactory->createRequest(
                    'GET',
                    'https://api.github.com/repos/' . $this->githubRepo . '/pulls/' . $this->pullNumber,
                    [
                        'Accept' => 'application/vnd.github.v3.html+json',
                        'User-Agent' => 'f43.me / Github Extractor',
                    ]
                );
                $response = $this->client->sendRequest($authentication->authenticate($request));
                $data = $this->jsonDecode($response);

                return '<div><em>Pull request on Github</em>' .
                    '<h2><a href="' . $data['base']['repo']['html_url'] . '">' . $data['base']['repo']['full_name'] . '</a></h2>' .
                    '<p>' . $data['base']['repo']['description'] . '</p>' .
                    '<h3>PR: <a href="' . $data['html_url'] . '">' . $data['title'] . '</a></h3>' .
                    '<ul><li>by <a href="' . $data['user']['html_url'] . '">' . $data['user']['login'] . '</a></li>' .
                    '<li>on ' . date('d/m/Y', strtotime($data['created_at'])) . '</li>' .
                    '<li>' . $data['commits'] . ' commits</li>' .
                    '<li>' . $data['comments'] . ' comments</li></ul>' .
                    $data['body_html'] . '</div>';
            } catch (\Exception $e) {
                $this->logger->error('Github (pull) extract failed for: ' . $this->githubRepo . ' & pr: ' . $this->pullNumber, [
                    'exception' => $e,
                ]);

                return '';
            }
        }

        if (null !== $this->issueNumber) {
            try {
                $request = $messageFactory->createRequest(
                    'GET',
                    'https://api.github.com/repos/' . $this->githubRepo . '/issues/' . $this->issueNumber,
                    [
                        'Accept' => 'application/vnd.github.v3.html+json',
                        'User-Agent' => 'f43.me / Github Extractor',
                    ]
                );
                $response = $this->client->sendRequest($authentication->authenticate($request));
                $data = $this->jsonDecode($response);

                return '<div><em>Issue on Github</em>' .
                    '<h2><a href="' . $data['html_url'] . '">' . $data['title'] . '</a></h2>' .
                    '<ul><li>by <a href="' . $data['user']['html_url'] . '">' . $data['user']['login'] . '</a></li>' .
                    '<li>on ' . date('d/m/Y', strtotime($data['created_at'])) . '</li>' .
                    '<li>' . $data['comments'] . ' comments</li></ul></ul>' .
                    $data['body_html'] . '</div>';
            } catch (\Exception $e) {
                $this->logger->error('Github (issue) extract failed for: ' . $this->githubRepo . ' & issue: ' . $this->issueNumber, [
                    'exception' => $e,
                ]);

                return '';
            }
        }

        try {
            $request = $messageFactory->createRequest(
                'GET',
                'https://api.github.com/repos/' . $this->githubRepo . '/readme',
                [
                    'Accept' => 'application/vnd.github.v3.html+json',
                    'User-Agent' => 'f43.me / Github Extractor',
                ]
            );

            return (string) $this->client->sendRequest($authentication->authenticate($request))->getBody();
        } catch (\Exception $e) {
            // Github will return a 404 if no readme are found
            return '';
        }
    }
}
