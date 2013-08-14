<?php

namespace j0k3r\FeedBundle\Readability;

use Doctrine\Common\Util\Inflector;
use j0k3r\FeedBundle\Parser;
use TubeLink\TubeLink;

class Proxy
{
    protected
        $feed = null,
        $buzz,
        $urlApi,
        $token,
        $debug,
        $regexps,
        $choosenParser = null,
        $allowAllParser = false,
        $availableParsers = array('Internal', 'External')
    ;

    public
        $url,
        $content,
        $useDefault = false
    ;

    public function __construct($buzz, $token, $urlApi, $debug = false, $regexps = array())
    {
        $this->buzz = $buzz;
        $this->token = $token;
        $this->urlApi = $urlApi;
        $this->debug = $debug;
        $this->regexps = $regexps;
    }

    public function setChoosenParser($parser)
    {
        $this->choosenParser = $parser;

        return $this;
    }

    public function setFeed($feed)
    {
        $this->feed = $feed;

        return $this;
    }

    /**
     * Define if we have to use all *known* parser to get the content if the defined one failed.
     * For example, Internal parser can't make content readable, it will use the External one, etc ..
     *
     * @param bool $value
     *
     * @return \Proxy Current object
     */
    public function allowAllParser($value)
    {
        $this->allowAllParser = (bool) $value;

        return $this;
    }

    /**
     * Try to retrieve content from a given url
     *
     * @param string $url         RSS item url
     * @param string $itemContent RSS item content, which will be taken if we can't extract content from url
     *
     * @return string
     */
    public function parseContent($url, $itemContent = null)
    {
        // we use default parser
        $customParser = new Parser\DefaultParser($url, $itemContent);

        // or try to find a custom one
        if (null !== $this->feed) {
            // I don't know why I have to use the *full* path to test if the class exists
            // even if the current classe "use" j0k3r\FeedBundle\Parser ...
            $name = Inflector::classify(str_replace('.', '-', $this->feed->getHost()));
            $customMethod = 'j0k3r\FeedBundle\Parser\\'.$name.'Parser';

            if (class_exists($customMethod)) {
                $customParser = new $customMethod($url, $itemContent);
            }
        }

        // retrieve custom url ?
        $this->url = $customParser->retrieveUrl();

        $parserMethod = 'use'.Inflector::camelize($this->choosenParser).'Parser';

        if (is_callable(array($this, $parserMethod))) {
            $this->content = $this->$parserMethod($this->url);
        }

        // if we allow all parser to be tested to get content, loop through all of them
        if (false === $this->content && true === $this->allowAllParser) {
            foreach ($this->availableParsers as $method) {
                // don't try the previous parser, which fails
                if (Inflector::camelize($this->choosenParser) == $method) {
                    continue;
                }

                $parserMethod = 'use'.Inflector::camelize($method).'Parser';
                $this->content = $this->$parserMethod($this->url);

                // once one parser succeed, we stop
                if (false !== $this->content) {
                    break;
                }
            }
        }

        // do something when readabled content failed
        if (!$this->content) {
            $this->content = $itemContent;
            $this->useDefault = true;
        } else {
            // update readable content with something ?
            $this->content = $customParser->updateContent($this->content);
        }

        return $this;
    }

    /**
     * Retrieve content from an internal library instead of a webservice.
     * It's a fallback by default, but can be the only solution if specified
     *
     * @param string $content
     *
     * @return string
     */
    private function useInternalParser($url)
    {
        // If it's a video, just return an embed html content
        try {
            return TubeLink::create()
                ->parse(htmlspecialchars_decode($url))
                ->render();
        } catch (\TubeLink\Exception\ServiceNotFoundException $e) {
            // it means it's not a video, let's try other content !
        }

        try {
            $response = $this->buzz->get($url);
            $content  = $response->getContent();
        } catch (\Exception $e) {
            // catch timeout, ssl verification that failed, etc ...
            return false;
        }

        if (false === $content) {
            return false;
        }

        // remove utm parameters & fragment
        $this->url = preg_replace('/((\?)?(&(amp;)?)?utm_(.*?)\=[^&]+)|(#(.*?)\=[^&]+)/', '', $this->buzz->getClient()->getInfo(CURLINFO_EFFECTIVE_URL));

        // save information about gzip content for later decoding
        $is_gziped = (bool) 'gzip' == $response->getHeader('Content-Encoding');

        $contentType = $response->getHeader('Content-Type');
        // if it's a binary file (in fact, not a 'text'), we handle it differently
        if (false === strpos($contentType, 'text')) {
            // if content is an image, just return it
            if (0 == strpos($contentType, 'image')) {
                return '<img src="'.$url.'" />';
            }

            // if it's not an image, we don't know how to render it
            // so we act that we can't make it readable
            return false;
        }

        // decode gzip content (most of the time it's a Tumblr website)
        if (true === $is_gziped) {
            $content = $this->gzdecode($content);
        }

        // Convert encoding since Readability accept only UTF-8
        if ('UTF-8' != mb_detect_encoding($content, mb_detect_order(), true)) {
            $content = mb_convert_encoding($content, 'UTF-8');
        }

        // let's clean up input.
        $tidy = tidy_parse_string($content, array(), 'UTF8');
        $tidy->cleanRepair();

        $readability          = new ReadabilityExtended($tidy->value, $this->url);
        $readability->debug   = $this->debug;
        $readability->regexps = $this->regexps;
        $readability->convertLinksToFootnotes = false;

        if (!$readability->init()) {
            return false;
        }

        $tidy = tidy_parse_string(
            $readability->getContent()->innerHTML,
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

    /**
     * Retrieve content from an external webservice.
     * In this case, we use the excellent Readability web service: https://www.readability.com/developers/api/parser
     *
     * @param string $url
     *
     * @return string
     */
    private function useExternalParser($url)
    {
        try {
            $response = $this->buzz->get($this->urlApi.'?token='.$this->token.'&url='.urlencode($url));
            $html = json_decode($response->getContent());
        } catch (\Exception $e) {
            return false;
        }

        if (isset($html->content)) {
            $this->url = $html->url;

            return $html->content;
        }

        if (isset($html->error)) {
            return $html->messages;
        }

        return false;
    }

    /**
     * Emulate gzdecode function that is available only for PHP >= 5.4
     */
    private function gzdecode($data)
    {
        if (function_exists('gzencode')) {
            return \gzencode($data);
        }

        $g = tempnam('/tmp', 'ff');
        @file_put_contents($g, $data);
        ob_start();
        readgzfile($g);

        return ob_get_clean();
    }
}
