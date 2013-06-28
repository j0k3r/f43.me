<?php

namespace j0k3r\FeedBundle\Readability;

use Doctrine\Common\Util\Inflector;
use j0k3r\FeedBundle\Parser;

class Proxy
{
    protected
        $feedSlug = null,
        $urlApi,
        $token,
        $debug,
        $convertLinksToFootnotes,
        $regexps,
        $choosenParser = null
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

    public function setFeedSlug($slug)
    {
        $this->feedSlug = $slug;

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
        if (null !== $this->feedSlug) {
            // I don't know why I have to use the *full* path to test if the class exists
            // even if the current classe "use" j0k3r\FeedBundle\Parser ...
            $customMethod = 'j0k3r\FeedBundle\Parser\\'.Inflector::classify($this->feedSlug).'Parser';

            if (class_exists($customMethod)){
                $customParser = new $customMethod($url, $itemContent);
            }
        }

        // retrieve custom url ?
        $url = $customParser->retrieveUrl();

        if (is_callable(array($this, $parserMethod))) {
            $this->content = $this->$parserMethod($url);
        } else {
            // use internal by default
            $this->content = $this->useInternalParser($url);
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
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        // curl_setopt($ch, CURLOPT_NOBODY, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        // curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, 'readHeaders'));
        // curl_setopt($ch, CURLOPT_WRITEFUNCTION, array($this, 'readBody'));
        $content = curl_exec($ch);
        curl_close($ch);

        $location = $url;
        // find last occurence of "Location: h" to be sure it isn't a local redirect (like /new_location)
        if ($content != NULL && ($location_raw = strripos($content, "Location: h")) !== FALSE ) {
            $location_raw += strlen("Location: h");
            $length       = (strpos($content, "\n", $location_raw) !== FALSE) ? strpos($content, "\n", $location_raw) - $location_raw : '';
            $location     = 'h'.trim(substr($content, $location_raw, $length));
        }

        // remove utm parameters & fragment
        $this->url = preg_replace('/(&?utm_(.*?)\=[^&]+)|(#(.*?)\=[^&]+)/', '', html_entity_decode($location));

        // Remove headers (once the url has been extract)
        // grabbed from: https://github.com/hugochinchilla/curl/blob/e6b1a1277f41b95f8247ff690873f3194194194f/lib/curl_response.php#L38-52
        $pattern = '#HTTP/\d\.\d.*?$.*?\r\n\r\n#ims';

        // Extract headers from content
        preg_match_all($pattern, $content, $matches);
        $headers_string = array_pop($matches[0]);
        $headers = explode("\r\n", str_replace("\r\n\r\n", '', $headers_string));

        // Inlude all received headers in the $headers_string
        while (count($matches[0])) {
          $headers_string = array_pop($matches[0]).$headers_string;
        }

        // Remove all headers from the response body
        $content = str_replace($headers_string, '', $content);

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
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

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
}
