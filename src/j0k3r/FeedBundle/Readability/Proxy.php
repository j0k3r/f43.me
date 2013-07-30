<?php

namespace j0k3r\FeedBundle\Readability;

use Doctrine\Common\Util\Inflector;
use j0k3r\FeedBundle\Parser;
use TubeLink\TubeLink;

class Proxy
{
    protected
        $feed = null,
        $urlApi,
        $token,
        $debug,
        $convertLinksToFootnotes,
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

    public function __construct($token, $urlApi, $debug = false, $convertLinksToFootnotes = false, $regexps = array())
    {
        $this->token = $token;
        $this->urlApi = $urlApi;
        $this->debug = $debug;
        $this->convertLinksToFootnotes = $convertLinksToFootnotes;
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
     * @param  bool   $value
     *
     * @return \Proxy          Current object
     */
    public function allowAllParser($value)
    {
        $this->allowAllParser = (bool) $value;

        return $this;
    }

    /**
     * Try to retrieve content from a given url
     *
     * @param  string   $url             RSS item url
     * @param  string   $itemContent     RSS item content, which will be taken if we can't extract content from url
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

            if (class_exists($customMethod)){
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
     * First step is to retrieve the real url, thanks to `CURLOPT_FOLLOWLOCATION`
     * @source http://link.chrislaskey.com/?source=true
     *
     * @param  string   $content
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

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($ch);
        curl_close($ch);

        // save information about gzip content for later decoding
        $is_gziped = (bool) strripos($content, 'Content-Encoding: gzip');

        // determine if it's a binary file. In that case, handle it differently
        if (!strripos($content, 'Content-type: text')) {
            $cTypePos = strripos($content, 'Content-type: ') + strlen('Content-type: ');
            $length   = (strpos($content, "\n", $cTypePos) !== false) ? strpos($content, "\n", $cTypePos) - $cTypePos : '';
            $mimeType = trim(substr($content, $cTypePos, $length));

            // if content is an image, just return it
            if (preg_match('/(jpe?g|gif|png)$/i', $mimeType)) {
                return '<img src="'.$url.'" />';
            }

            // if it's not an image, we don't know how to render it
            // so we act that we can't make it readable
            return false;
        }

        $location = $url;
        // find last occurence of "Location: h" to be sure it isn't a local redirect (like /new_location)
        if ($content != null && ($location_raw = strripos($content, "Location: h")) !== false) {
            $location_raw += strlen("Location: h");
            $length       = (strpos($content, "\n", $location_raw) !== false) ? strpos($content, "\n", $location_raw) - $location_raw : '';
            $location     = 'h'.trim(substr($content, $location_raw, $length));
        }

        // remove utm parameters & fragment
        $this->url = preg_replace('/(&?utm_(.*?)\=[^&]+)|(#(.*?)\=[^&]+)/', '', html_entity_decode($location));

        $content = $this->removeHeader($content);

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

        $readability          = new ReadabilityExtended($tidy->value, $this->url);
        $readability->debug   = $this->debug;
        $readability->regexps = $this->regexps;
        $readability->convertLinksToFootnotes = $this->convertLinksToFootnotes;

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
     * @param  string   $url
     *
     * @return string
     */
    private function useExternalParser($url)
    {
        $url = $this->urlApi.'?token='.$this->token.'&url='.urlencode($url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $html = json_decode(curl_exec($ch));

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
     * Remove headers (once the url has been extract)
     * Grabbed from: https://github.com/hugochinchilla/curl/blob/e6b1a1277f41b95f8247ff690873f3194194194f/lib/curl_response.php#L38-52
     *
     * @param  string   $content Content with header
     *
     * @return string
     */
    private function removeHeader($content) {
        $pattern = '#HTTP/\d\.\d.*?$.*?\r\n\r\n#ims';

        // Extract headers from content
        preg_match_all($pattern, $content, $matches);
        $headers_string = array_pop($matches[0]);

        // Inlude all received headers in the $headers_string
        while (count($matches[0])) {
          $headers_string = array_pop($matches[0]).$headers_string;
        }

        // Remove all headers from the response body
        return str_replace($headers_string, '', $content);
    }
}
