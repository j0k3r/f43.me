<?php

namespace j0k3r\FeedBundle\Parser;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\RequestException;

use TubeLink\TubeLink;
use TubeLink\Exception\ServiceNotFoundException;

use j0k3r\FeedBundle\Readability\ReadabilityExtended;

/**
 * Retrieve content from an internal library instead of a webservice.
 * It's a fallback by default, but can be the only solution if specified
 */
class Internal extends AbstractParser
{
    protected $guzzle;
    protected $regexps = array();

    /**
     *
     * @param Client $guzzle
     * @param array  $regexps Regex to remove/escape content
     */
    public function __construct(Client $guzzle, $regexps = array())
    {
        $this->guzzle = $guzzle;
        $this->regexps = $regexps;
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
            $response = $this->guzzle->get($url)->send();
            $content  = $response->getBody();
        } catch (RequestException $e) {
            // catch timeout, ssl verification that failed, etc ...
            return '';
        }

        if (false === $content) {
            return '';
        }

        // save information about gzip content for later decoding
        $is_gziped = 'gzip' == $response->getHeader('Content-Encoding');

        $contentType = (string) $response->getHeader('Content-Type');

        // if it's a binary file (in fact, not a 'text'), we handle it differently
        if (false === strpos($contentType, 'text')) {
            // if content is an image, just return it
            if (0 === strpos($contentType, 'image')) {
                return '<img src="'.$url.'" />';
            }

            // if it's not an image, we don't know how to render it
            // so we act that we can't make it readable
            return '';
        }

        // decode gzip content (most of the time it's a Tumblr website)
        if (true === $is_gziped) {
            $content = gzdecode($content);
        }

        // Convert encoding since Readability accept only UTF-8
        if ('UTF-8' != mb_detect_encoding($content, mb_detect_order(), true)) {
            $content = mb_convert_encoding($content, 'UTF-8');
        }

        // let's clean up input.
        $tidy = tidy_parse_string($content, array(), 'UTF8');
        $tidy->cleanRepair();

        $readability          = new ReadabilityExtended($tidy->value, $url);
        // $readability->debug   = $this->debug;
        $readability->regexps = $this->regexps;
        $readability->convertLinksToFootnotes = false;

        if (!$readability->init()) {
            return '';
        }

        $tidy = tidy_parse_string(
            $readability->getHtmlContent(),
            array(
                'wrap'           => 0,
                'indent'         => true,
                'show-body-only' => true
            ),
            'UTF8'
        );
        $tidy->cleanRepair();

        return $tidy->value;
    }
}
