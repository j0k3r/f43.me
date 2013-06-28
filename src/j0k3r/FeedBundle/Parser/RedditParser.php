<?php

namespace j0k3r\FeedBundle\Parser;

/**
 * RedditParser
 *
 * This class provides a custom parser for reddit feeds
 */
class RedditParser extends DefaultParser
{
    public function retrieveUrl()
    {
        // we extract the source of the reddit post
        preg_match('/(.*)\<a href\=\"(.*)\"\>\[link\]\<\/a\>/i', $this->itemContent, $matches);
        if (count($matches) != 3) {
            return $url;
        }

        return $matches[2];
    }

    public function updateContent($content)
    {
        return $this->itemContent.'<br/><hr/><br/>'.$content;
    }
}
