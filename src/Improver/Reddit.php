<?php

namespace App\Improver;

/**
 * Reddit Improver.
 *
 * This class provides a custom parser for reddit feeds
 */
class Reddit extends DefaultImprover
{
    public function match(string $host): bool
    {
        return \in_array($host, ['reddit.com', 'www.reddit.com'], true);
    }

    /**
     * For reddit we extract link for the default content retrieved.
     * Because the rss item link goes to reddit. The important one is inside the content.
     *
     * {@inheritdoc}
     */
    public function updateUrl(string $url): string
    {
        // we extract the source of the reddit post
        preg_match('/(.*)\<a href\=\"(.*)\"\>\[link\]\<\/a\>/i', $this->itemContent, $matches);
        if (3 !== \count($matches)) {
            return $url;
        }

        return str_replace('&amp;', '&', $matches[2]);
    }

    /**
     * We just happen the readable item to the default one.
     *
     * {@inheritdoc}
     */
    public function updateContent(string $readableContent): string
    {
        return $this->itemContent . '<br/><hr/><br/>' . $readableContent;
    }
}
