<?php

namespace Illuminate\Foundation\Testing\Constraints;

class IsChecked extends FormFieldConstraint
{
    /**
     * Return a new is checked instance.
     *
     * @param  string  $selector element's name or ID
     * @return void
     */
    public function __construct($selector)
    {
        $this->selector = $selector;
    }

    /**
     * Get the valid elements.
     *
     * Multiple elements should be separated by commas without spaces.
     *
     * @return string
     */
    protected function validElements()
    {
        return "input[type='checkbox']";
    }

    /**
     * Check if the checkbox is found and checked in the given crawler.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler|string  $crawler
     * @return bool
     */
    public function matches($crawler)
    {
        $crawler = $this->crawler($crawler);

        return $this->field($crawler)->attr('checked') !== null;
    }

    /**
     * Return the description of the failure.
     *
     * @return string
     */
    protected function getFailureDescription()
    {
        return "the checkbox [{$this->selector}] is checked";
    }
}
