<?php

namespace Api43\FeedBundle\Parser;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use TubeLink\TubeLink;
use TubeLink\Exception\ServiceNotFoundException;
use Api43\FeedBundle\Readability\ReadabilityExtended;
use Readability\Readability;

/**
 * Retrieve content from an internal library instead of a webservice.
 * It's a fallback by default, but can be the only solution if specified.
 */
class Internal extends AbstractParser
{
    protected $guzzle;
    protected $regexps = array();

    /**
     * @param Client $guzzle
     * @param array  $regexps Regex to remove/escape content
     */
    public function __construct(Client $guzzle, $regexps = array())
    {
        $this->guzzle = $guzzle;
        $this->regexps = $regexps;
    }

    /**
     * Checks if the Content-Type is of a certain type.  This is useful if the
     * Content-Type header contains charset information and you need to know if
     * the Content-Type matches a particular type.
     *
     * @param GuzzleHttp\Message\Response $response
     * @param string $type Content type to check against
     *
     * @return bool
     */
    private function isContentType(\GuzzleHttp\Message\Response $response, $type)
    {
        return stripos($response->getHeader('Content-Type'), $type) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function parse($url)
    {
        // If it's a video, just return an embed html content
        try {
            return TubeLink::create()
                ->parse(htmlspecialchars_decode($url))
                ->render();
        } catch (ServiceNotFoundException $e) {
            // it means it's not a video, let's try other content !
            $content = '';
        }

        try {
            $response = $this->guzzle->get($url);
            $content = $response->getBody();

            // if it's a binary file (in fact, not a 'text'), we handle it differently
            if (!$this->isContentType($response, 'text')) {
                // if content is an image, just return it
                if ($this->isContentType($response, 'image')) {
                    return '<img src="'.$url.'" />';
                }

                // if it's not an image, we don't know how to render it
                // so we act that we can't make it readable
                return '';
            }

            // decode gzip content (most of the time it's a Tumblr website)
            if ('gzip' == (string) $response->getHeader('Content-Encoding')) {
                $content = gzdecode($content);
            }

            if (!$this->isContentType($response, 'utf-8') && !mb_check_encoding($content, 'UTF-8')) {
                $content = mb_convert_encoding($content, 'UTF-8');
            }
        } catch (RequestException $e) {
            // catch timeout, ssl verification that failed, etc ...
            // so try an alternative using basic file_get_contents
            $content = @file_get_contents($url, false, stream_context_create(array(
                'http' => array('timeout' => 10),
            )));

            // all characters are not printable, this mean it's not a string but a binary
            if (false === ctype_print($content)) {
                return '';
            }
        }

        if (false === $content) {
            return '';
        }

        $readability = new ReadabilityExtended($content, $url);
        // $readability->debug = true;
        $readability->regexps = $this->regexps;
        $readability->convertLinksToFootnotes = false;

        if (!$readability->init()) {
            return '';
        }

        $tidy = tidy_parse_string(
            $readability->getHtmlContent(),
            array(
                'wrap' => 0,
                'indent' => false,
                'show-body-only' => true,
            ),
            'UTF8'
        );
        $tidy->cleanRepair();

        return $tidy->value;
    }
}
