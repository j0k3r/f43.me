<?php

namespace j0k3r\FeedBundle\Services;

class ReadabilityProxy
{
    protected
        $token,
        $debug,
        $convertLink,
        $choosenParser = null
    ;

    public
        $url,
        $content
    ;

    public function __construct($token, $debug = false, $convertLink = false)
    {
        $this->token       = $token;
        $this->debug       = $debug;
        $this->convertLink = $convertLink;
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

        $this->url = $location;

        // let's clean up input.
        $tidy = tidy_parse_string($content, array(), 'UTF8');
        $tidy->cleanRepair();

        $readability = new \Readability($tidy->value);
        $readability->debug = $this->debug;
        $readability->convertLinksToFootnotes = $this->convertLink;

        if (!$readability->init()) {
            return false;
        }

        $tidy = tidy_parse_string(
            $readability->getContent()->innerHTML,
            array(
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
        $url = 'https://readability.com/api/content/v1/parser?token='.$this->token.'&url='.urlencode($url);
        // $html = json_decode(file_get_contents($url));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        $html = json_decode(curl_exec($ch));

        if (isset($html->content)) {
            $this->url = $html->url;
            return $html->content;
        }

        return false;
    }
}
