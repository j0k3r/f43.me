<?php

namespace App\Extractor;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Message\Authentication\BasicAuth;

class Github extends AbstractExtractor
{
    /** @var string */
    protected $githubRepo;
    /** @var string */
    protected $releaseTag;
    /** @var string */
    protected $pullNumber;
    /** @var string */
    protected $issueNumber;

    public function __construct(protected string $githubClientId, protected string $githubClientSecret)
    {
    }

    public function match(string $url): bool
    {
        $host = parse_url($url, \PHP_URL_HOST);
        $path = parse_url($url, \PHP_URL_PATH);

        if (null === $host || null === $path) {
            return false;
        }

        if (!str_contains((string) $host, 'github.com')) {
            return false;
        }

        // find github user and project only
        preg_match('/^\/([\w\d\.-]+)\/([\w\d\.-]+)\/?$/i', (string) $path, $matches);

        if (3 === \count($matches)) {
            $this->githubRepo = $matches[1] . '/' . $matches[2];

            return true;
        }

        // find pull request or issue
        preg_match('/^\/([\w\d\.-]+)\/([\w\d\.-]+)\/(pull|issues)\/([0-9]+)/i', (string) $path, $matches);

        if (5 === \count($matches)) {
            $this->githubRepo = $matches[1] . '/' . $matches[2];

            if ('pull' === $matches[3]) {
                $this->pullNumber = $matches[4];
            } elseif ('issues' === $matches[3]) {
                $this->issueNumber = $matches[4];
            }

            return true;
        }

        // find release
        preg_match('/^\/([\w\d\.-]+)\/([\w\d\.-]+)\/releases\/(.*)/i', (string) $path, $matches);

        if (4 === \count($matches)) {
            $this->githubRepo = $matches[1] . '/' . $matches[2];
            $this->releaseTag = $matches[3];

            return true;
        }

        return false;
    }

    public function getContent(): string
    {
        if (!$this->githubRepo) {
            return '';
        }

        $authentication = new BasicAuth($this->githubClientId, $this->githubClientSecret);
        $messageFactory = Psr17FactoryDiscovery::findRequestFactory();

        if (null !== $this->pullNumber) {
            try {
                $request = $messageFactory
                    ->createRequest(
                        'GET',
                        'https://api.github.com/repos/' . $this->githubRepo . '/pulls/' . $this->pullNumber
                    )
                    ->withHeader('Accept', 'application/vnd.github.v3.html+json')
                    ->withHeader('User-Agent', 'f43.me / Github Extractor')
                ;
                $response = $this->client->sendRequest($authentication->authenticate($request));
                $data = $this->jsonDecode($response);

                return '<div><em>Pull request on Github</em>' .
                    '<h2><a href="' . $data['base']['repo']['html_url'] . '">' . $data['base']['repo']['full_name'] . '</a></h2>' .
                    '<p>' . $data['base']['repo']['description'] . '</p>' .
                    '<h3>PR: <a href="' . $data['html_url'] . '">' . $data['title'] . '</a></h3>' .
                    '<ul><li>by <a href="' . $data['user']['html_url'] . '">' . $data['user']['login'] . '</a></li>' .
                    '<li>on ' . date('d/m/Y', (int) strtotime((string) $data['created_at'])) . '</li>' .
                    '<li>' . $data['commits'] . ' commits</li>' .
                    '<li>' . $data['comments'] . ' comments</li></ul>' .
                    $data['body_html'] . '</div>';
            } catch (\Exception $e) {
                $this->logger->error('Github (pull) extract failed for: ' . $this->githubRepo . ' & pr: ' . $this->pullNumber, [
                    'exception' => $e,
                ]);

                return '';
            }
        } elseif (null !== $this->issueNumber) {
            try {
                $request = $messageFactory
                    ->createRequest(
                        'GET',
                        'https://api.github.com/repos/' . $this->githubRepo . '/issues/' . $this->issueNumber
                    )
                    ->withHeader('Accept', 'application/vnd.github.v3.html+json')
                    ->withHeader('User-Agent', 'f43.me / Github Extractor')
                ;
                $response = $this->client->sendRequest($authentication->authenticate($request));
                $data = $this->jsonDecode($response);

                return '<div><em>Issue on Github</em>' .
                    '<h2><a href="' . $data['html_url'] . '">' . $data['title'] . '</a></h2>' .
                    '<ul><li>by <a href="' . $data['user']['html_url'] . '">' . $data['user']['login'] . '</a></li>' .
                    '<li>on ' . date('d/m/Y', (int) strtotime((string) $data['created_at'])) . '</li>' .
                    '<li>' . $data['comments'] . ' comments</li></ul>' .
                    $data['body_html'] . '</div>';
            } catch (\Exception $e) {
                $this->logger->error('Github (issue) extract failed for: ' . $this->githubRepo . ' & issue: ' . $this->issueNumber, [
                    'exception' => $e,
                ]);

                return '';
            }
        } elseif (null !== $this->releaseTag) {
            try {
                $request = $messageFactory
                    ->createRequest(
                        'GET',
                        'https://api.github.com/repos/' . $this->githubRepo . '/releases/tags/' . $this->releaseTag
                    )
                    ->withHeader('Accept', 'application/vnd.github.v3.html+json')
                    ->withHeader('User-Agent', 'f43.me / Github Extractor')
                ;
                $response = $this->client->sendRequest($authentication->authenticate($request));
                $data = $this->jsonDecode($response);

                return '<div><em>Release on Github</em>' .
                    '<h2><a href="' . $data['html_url'] . '">' . $data['name'] . ' (' . $data['tag_name'] . ')</a></h2>' .
                    '<ul><li>repo <strong>' . $this->githubRepo . '</strong></li>' .
                    '<li>on ' . date('d/m/Y', (int) strtotime((string) $data['published_at'])) . '</li>' .
                    '<li>' . $data['reactions']['total_count'] . ' reactions</li></ul>' .
                    $data['body_html'] . '</div>';
            } catch (\Exception $e) {
                $this->logger->error('Github (release) extract failed for: ' . $this->githubRepo . ' & release: ' . $this->releaseTag, [
                    'exception' => $e,
                ]);

                return '';
            }
        }

        try {
            $request = $messageFactory
                ->createRequest(
                    'GET',
                    'https://api.github.com/repos/' . $this->githubRepo . '/readme'
                )
                ->withHeader('Accept', 'application/vnd.github.v3.html+json')
                ->withHeader('User-Agent', 'f43.me / Github Extractor')
            ;

            return (string) $this->client->sendRequest($authentication->authenticate($request))->getBody();
        } catch (\Exception) {
            // Github will return a 404 if no readme are found
            return '';
        }
    }
}
