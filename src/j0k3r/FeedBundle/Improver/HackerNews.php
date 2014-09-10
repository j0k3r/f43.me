<?php

namespace j0k3r\FeedBundle\Improver;

/**
 * HackerNews Improver
 *
 * This class provides a custom parser for news.ycombinator.com feed
 */
class HackerNews extends Nothing
{
    /**
     * {@inheritdoc}
     */
    public function match($host)
    {
        return 0 === strpos('news.ycombinator.com', $host) ? true : false;
    }

    /**
     * We append the readable content to something a bit more friendly:
     * - a link to the original article with the host as value
     * - a link to comments on Hacker News
     *
     * @inheritdoc
     */
    public function updateContent($readableContent)
    {
        $host = parse_url($this->url, PHP_URL_HOST);

        // $itemContent for HackerNews feed contains only a link to the HN page with "Comments" as name
        return '<p><em>Original article on <a href="'.$this->url.'">'.$host.'</a> - '.$this->itemContent.' on Hacker News</em></p> '.$readableContent;
    }
}
