<?php

namespace j0k3r\FeedBundle\Readability;

use j0k3r\FeedBundle\Readability\AbsoluteUrl;

/**
 * This class extends the Readability one to add more fine tuning on content:
 *     - remove some unwanted attributes
 *     - convert relative path to absolute
 *
 */
class ReadabilityExtended extends \Readability
{
    /**
     * AbsoluteUrl object
     *
     * @var string
     */
    public $absUrl;

    function __construct($html, $url = null, $parser = 'libxml')
    {
        $this->absUrl = new AbsoluteUrl();

        parent::__construct($html, $url, $parser);
    }

    /**
     * Prepare the article node for display. Clean out any inline styles,
     * iframes, forms, strip extraneous <p> tags, etc.
     *
     * @see parent
     * @param DOMElement
     * @return void
     */
    public function prepArticle($articleContent)
    {
        $this->cleanTags($articleContent);
        $this->cleanAttrs($articleContent);
        $this->makeImgSrcAbsolute($articleContent);
        $this->makeHrefAbsolute($articleContent);

        parent::prepArticle($articleContent);
    }

    /**
     * Remove some attributes on every $e and under.
     *
     * @param DOMElement $e
     * @return void
     */
    public function cleanAttrs($e)
    {
        if (!is_object($e)) return;

        $attrs = explode('|', $this->regexps['attrToRemove']);

        $elems = $e->getElementsByTagName('*');
        foreach ($elems as $elem) {
            foreach ($attrs as $attr) {
                $elem->removeAttribute($attr);
            }
        }
    }

    /**
     * Remove some "bad" tags on every $e and under.
     *
     * @param DOMElement $e
     * @return void
     */
    public function cleanTags($e)
    {
        if (!is_object($e)) return;

        $tags = explode('|', $this->regexps['tagToRemove']);

        foreach ($tags as $tag) {
            $this->clean($e, $tag);
        }
    }

    /**
     * Convert relative image path to absolute
     *
     * @param  DOMElement   $e
     * @return void
     */
    public function makeImgSrcAbsolute($e)
    {
        if (!is_object($e)) return;

        $elems = $e->getElementsByTagName('img');
        foreach ($elems as $elem) {
            // hu oh img node without src, remove it.
            if (!$elem->hasAttribute('src')) {
                $elem->parentNode->removeChild($elem);
                continue;
            }

            $src = $elem->getAttribute('src');

            // handle image src that are converted by javascript (lazy load)
            if ($elem->hasAttribute('originalsrc')) {
                $src = $elem->getAttribute('originalsrc');
                $elem->removeAttribute('originalsrc');
                $elem->setAttribute('src', $src);
            }
            if ($elem->hasAttribute('data-src')) {
                $src = $elem->getAttribute('data-src');
                $elem->removeAttribute('data-src');
                $elem->setAttribute('src', $src);
            }

            if (preg_match('/^http(s?):\/\//i', $src)) {
                continue;
            }

            // convert relative src to absolute
            $src = $this->absUrl->url_to_absolute(
                $this->url,
                $src
            );

            $elem->setAttribute('src', $src);
        }
    }

    public function makeHrefAbsolute($e)
    {
        if (!is_object($e)) return;

        $elems = $e->getElementsByTagName('a');
        foreach ($elems as $elem) {
            $href = $elem->getAttribute('href');

            if (preg_match('/^http(s?):\/\//i', $href)) {
                continue;
            }

            // convert relative href to absolute
            $href = $this->absUrl->url_to_absolute(
                $this->url,
                $href
            );

            $elem->setAttribute('href', $href);
        }
    }
}
