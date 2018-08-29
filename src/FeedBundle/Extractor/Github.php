<?php

namespace Api43\FeedBundle\Extractor;

use GuzzleHttp\Exception\RequestException;

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

        if (false === $path || false === $host) {
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

        if (null !== $this->pullNumber) {
            try {
                $data = $this->client
                    ->get(
                        'https://api.github.com/repos/' . $this->githubRepo . '/pulls/' . $this->pullNumber,
                        [
                            'headers' => [
                                'Accept' => 'application/vnd.github.v3.html+json',
                                'User-Agent' => 'f43.me / Github Extractor',
                            ],
                            'query' => [
                                'client_id' => $this->githubClientId,
                                'client_secret' => $this->githubClientSecret,
                            ],
                        ]
                    )
                    ->json();

                return '<div><em>Pull request on Github</em>' .
                    '<h2><a href="' . $data['base']['repo']['html_url'] . '">' . $data['base']['repo']['full_name'] . '</a></h2>' .
                    '<p>' . $data['base']['repo']['description'] . '</p>' .
                    '<h3>PR: <a href="' . $data['html_url'] . '">' . $data['title'] . '</a></h3>' .
                    '<ul><li>by <a href="' . $data['user']['html_url'] . '">' . $data['user']['login'] . '</a></li>' .
                    '<li>on ' . date('d/m/Y', strtotime($data['created_at'])) . '</li>' .
                    '<li>' . $data['commits'] . ' commits</li>' .
                    '<li>' . $data['comments'] . ' comments</li></ul>' .
                    $data['body_html'] . '</div>';
            } catch (RequestException $e) {
                $this->logger->error('Github (pull) extract failed for: ' . $this->githubRepo . ' & pr: ' . $this->pullNumber, [
                    'exception' => $e,
                ]);

                return '';
            }
        }

        if (null !== $this->issueNumber) {
            try {
                $data = $this->client
                    ->get(
                        'https://api.github.com/repos/' . $this->githubRepo . '/issues/' . $this->issueNumber,
                        [
                            'headers' => [
                                'Accept' => 'application/vnd.github.v3.html+json',
                                'User-Agent' => 'f43.me / Github Extractor',
                            ],
                            'query' => [
                                'client_id' => $this->githubClientId,
                                'client_secret' => $this->githubClientSecret,
                            ],
                        ]
                    )
                    ->json();

                return '<div><em>Issue on Github</em>' .
                    '<h2><a href="' . $data['html_url'] . '">' . $data['title'] . '</a></h2>' .
                    '<ul><li>by <a href="' . $data['user']['html_url'] . '">' . $data['user']['login'] . '</a></li>' .
                    '<li>on ' . date('d/m/Y', strtotime($data['created_at'])) . '</li>' .
                    '<li>' . $data['comments'] . ' comments</li></ul></ul>' .
                    $data['body_html'] . '</div>';
            } catch (RequestException $e) {
                $this->logger->error('Github (issue) extract failed for: ' . $this->githubRepo . ' & issue: ' . $this->issueNumber, [
                    'exception' => $e,
                ]);

                return '';
            }
        }

        try {
            return $this->client
                ->get(
                    'https://api.github.com/repos/' . $this->githubRepo . '/readme',
                    [
                        'headers' => [
                            'Accept' => 'application/vnd.github.v3.html',
                            'User-Agent' => 'f43.me / Github Extractor',
                        ],
                        'query' => [
                            'client_id' => $this->githubClientId,
                            'client_secret' => $this->githubClientSecret,
                        ],
                    ]
                )
                ->getBody();
        } catch (RequestException $e) {
            // Github will return a 404 if no readme are found
            return '';
        }
    }
}
