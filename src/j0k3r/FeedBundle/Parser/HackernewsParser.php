<?php

namespace j0k3r\FeedBundle\Parser;

/**
 * HackernewsParser
 *
 * This class provides a custom parser for news.ycombinator.com feed
 */
class HackernewsParser extends DefaultParser
{
    /**
     * We just append the readable content to the default content.
     * In fact, we transform the "Comments" link, to something a bit more friendly
     */
    public function updateContent($content)
    {
        $url_parts = parse_url($this->url);

        return '<p><em>Original article on <a href="'.$this->url.'">'.$url_parts['host'].'</a></em></p> '.$content;
    }
}
