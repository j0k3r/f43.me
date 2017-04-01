<?php

namespace Api43\FeedBundle\Converter;

use Api43\FeedBundle\Extractor\Youtube as YoutubeExtractor;

/**
 * This converter will try to change embed text from Youtube from a simple html code to a real iframe to be displayed.
 */
class Youtube extends AbstractConverter
{
    const IFRAME_CONTENT = '<iframe width="480" height="270" src="youtube_url" frameborder="0" allowfullscreen></iframe>';

    private $youtubeExtractor;

    public function __construct(YoutubeExtractor $youtubeExtractor)
    {
        $this->youtubeExtractor = $youtubeExtractor;
    }

    /**
     * This will convert all Youtube embed to real iframe.
     * The detection is a bit ugly because it's done using a regex to find Youtube content.
     * Mostly because html shouldn't be valid.
     *
     * @param string $html
     *
     * @return string
     */
    public function convert($html)
    {
        // extract url from "embedded content"
        $re = '/\[embedded content, src: "([a-z0-9:\/\.\?\&\=]+)"]/i';
        $res = preg_match_all($re, $html, $matches);

        if (false === $res || $res < 1) {
            return $html;
        }

        foreach ($matches[1] as $key => $embedUrl) {
            if (false === $this->youtubeExtractor->match($embedUrl)) {
                continue;
            }

            $newContent = str_replace('youtube_url', $embedUrl, self::IFRAME_CONTENT);

            $html = str_replace($matches[0][$key], $newContent, $html);
        }

        return $html;
    }
}
