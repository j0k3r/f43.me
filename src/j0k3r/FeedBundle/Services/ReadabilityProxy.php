<?php

namespace j0k3r\FeedBundle\Services;

class ReadabilityProxy
{
    protected
        $token,
        $debug,
        $convertLinksToFootnotes,
        $regexps,
        $choosenParser = null
    ;

    public
        $url,
        $content
    ;

    public function __construct($token, $debug = false, $convertLinksToFootnotes = false, $regexps = array())
    {
        $this->token = $token;
        $this->debug = $debug;
        $this->convertLinksToFootnotes = $convertLinksToFootnotes;
        $this->regexps = $regexps;
    }

    public function setChoosenParser($parser)
    {
        $this->choosenParser = $parser;

        return $this;
    }

    /**
     * Try to retrieve content from a given url
     *
     * @param  string   $url
     *
     * @return string
     */
    public function parseContent($url)
    {
        switch ($this->choosenParser)
        {
            case 'internal':
                $this->content = $this->useInternalParser($url);
                break;

            case 'external':
                $this->content = $this->useExternalParser($url);
                break;

            default:
                $this->content = $this->useInternalParser($url);
                if ($this->content) {
                    break;
                }

                $this->content = $this->useExternalParser($this->url);
                if ($this->content) {
                    break;
                }
        }

        // do something when readabled content failed
        if (!$this->content) {
            # code...
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

        $readability = new \Readability($tidy->value);
        $readability->debug = $this->debug;
        $readability->regexps = $this->regexps;
        $readability->convertLinksToFootnotes = $this->convertLinksToFootnotes;

        if (!$readability->init()) {
            return 'Can\'t retrieve content...';
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

        // hard way to convert relative image path to absolute path
        $host = parse_url($this->url, PHP_URL_HOST);
        $html = str_replace('src="/', 'src="//'.$host.'/', $tidy->value);
        // in case we replace src that starts with //
        $html = str_replace('//'.$host.'//', '//', $html);

        return $html;
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
        $url = 'https://readability.com/api/content/v1/parser?token='.$this->token.'&url='.urlencode($url);

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
