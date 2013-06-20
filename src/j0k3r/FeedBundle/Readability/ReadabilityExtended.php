<?php

namespace j0k3r\FeedBundle\Readability;

/**
 * This class extends the Readability one to add more fine tuning on content:
 *     - remove some unwanted attributes
 *     - convert relative path to absolute
 *
 */
class ReadabilityExtended extends \Readability
{
    /**
     * host used to convert relative image path to absolute
     *
     * @var string
     */
    public $host;

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
        $this->cleanAttrs($articleContent);
        $this->makeImgSrcAbsolute($articleContent);

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

            if (preg_match('/^(http)/i', $src)) {
                continue;
            }

            // replace src="/path to src="//host.com/path
            if (preg_match('/^\/(.*)/i', $src)) {
                $src = '//'.$this->host.$src;
            }
            // replace src="path to src="//host.com/path
            elseif (preg_match('/^(.*)/i', $src)) {
                $src = '//'.$this->host.'/'.$src;
            }

            $elem->setAttribute('src', $src);
        }
    }
}
