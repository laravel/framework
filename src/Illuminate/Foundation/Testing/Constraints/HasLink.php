<?php

namespace Illuminate\Foundation\Testing\Constraints;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;

class HasLink extends PageConstraint
{
    /**
     * The text expected to be found.
     *
     * @var string
     */
    protected $text;

    /**
     * The URL expected to be linked in the <a> tag.
     *
     * @var string|null
     */
    protected $url;

    /**
     * Create a new constraint instance.
     *
     * @param  string  $text
     * @param  string|null  $url
     * @return void
     */
    public function __construct($text, $url = null)
    {
        $this->url = $url;
        $this->text = $text;
    }

    /**
     * Check if the link is found in the given crawler.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler|string  $crawler
     * @return bool
     */
    public function matches($crawler)
    {
        $links = $this->crawler($crawler)->selectLink($this->text);

        if ($links->count() == 0) {
            return false;
        }

        // If the URL is null we assume the developer only wants to find a link
        // with the given text regardless of the URL. So if we find the link
        // we will return true. Otherwise, we will look for the given URL.
        if ($this->url == null) {
            return true;
        }

        $absoluteUrl = $this->absoluteUrl();

        foreach ($links as $link) {
            $linkHref = $link->getAttribute('href');

            if ($linkHref == $this->url || $linkHref == $absoluteUrl) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add a root if the URL is relative (helper method of the hasLink function).
     *
     * @param  string  $url
     * @return string
     */
    protected function absoluteUrl()
    {
        if (! Str::startsWith($this->url, ['http', 'https'])) {
            return URL::to($this->url);
        }

        return $this->url;
    }

    /**
     * Returns the description of the failure.
     *
     * @return string
     */
    public function getFailureDescription()
    {
        $description = "a link with the text [{$this->text}]";

        if ($this->url) {
            $description .= " and the URL [{$this->url}]";
        }

        return $description;
    }
}
