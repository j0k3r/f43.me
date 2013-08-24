<?php

namespace j0k3r\FeedBundle\Parser;

/**
 * NewsYcombinatorComParser
 *
 * This class provides a custom parser for news.ycombinator.com feed
 */
class NewsYcombinatorComParser extends DefaultParser
{
    /**
     * We append the readable content to something a bit more friendly:
     * - a link to the original article with the host as value
     * - a link to comments on Hacker News
     *
     * @see DefaultParser/updateContent
     *
     * @param string $content Readable item content
     *
     * @return string
     */
    public function updateContent($content)
    {
        $url_parts = parse_url($this->url);

        // $itemContent for HackerNews feed contains only a link to the HN page with "Comments" as name
        return '<p><em>Original article on <a href="'.$this->url.'">'.$url_parts['host'].'</a> - '.$this->itemContent.' on Hacker News</em></p> '.$content;
    }
}
