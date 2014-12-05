<?php

namespace Api43\FeedBundle\Improver;

/**
 * Reddit Improver
 *
 * This class provides a custom parser for reddit feeds
 */
class Reddit extends Nothing
{
    /**
     * {@inheritdoc}
     */
    public function match($host)
    {
        return 0 === strpos('reddit.com', $host) ? true : false;
    }

    /**
     * For reddit we extract link for the default content retrieved.
     * Because the rss item link goes to reddit. The important one is inside the content.
     *
     * {@inheritdoc}
     */
    public function updateUrl($url)
    {
        // we extract the source of the reddit post
        preg_match('/(.*)\<a href\=\"(.*)\"\>\[link\]\<\/a\>/i', $this->itemContent, $matches);
        if (count($matches) != 3) {
            return $url;
        }

        return $matches[2];
    }

    /**
     * We just happen the readable item to the default one
     *
     * {@inheritdoc}
     */
    public function updateContent($readableContent)
    {
        return $this->itemContent.'<br/><hr/><br/>'.$readableContent;
    }
}
