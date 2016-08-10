<?php

namespace Illuminate\Foundation\Testing\Constraints;

use Symfony\Component\DomCrawler\Crawler;

class HasElement extends PageConstraint
{
    /**
     * The name or ID of the element.
     *
     * @var string
     */
    protected $selector;

    /**
     * The attributes the element should have.
     *
     * @var array
     */
    protected $attributes;

    /**
     * Create a new constraint instance.
     *
     * @param  string  $selector
     * @param  array  $attributes
     * @return void
     */
    public function __construct($selector, array $attributes = [])
    {
        $this->selector = $selector;
        $this->attributes = $attributes;
    }

    /**
     * Check if the element is found in the given crawler.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler|string  $crawler
     * @return bool
     */
    public function matches($crawler)
    {
        $elements = $this->crawler($crawler)->filter($this->selector);

        if ($elements->count() == 0) {
            return false;
        }

        if (empty($this->attributes)) {
            return true;
        }

        $elements = $elements->reduce(function ($element) {
            return $this->hasAttributes($element);
        });

        return $elements->count() > 0;
    }

    /**
     * Determines if the given element has the attributes.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $element
     * @return bool
     */
    protected function hasAttributes(Crawler $element)
    {
        foreach ($this->attributes as $name => $value) {
            if (is_numeric($name)) {
                if ($element->attr($value) === null) {
                    return false;
                }
            } else {
                if ($element->attr($name) != $value) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function toString()
    {
        $message = "the element [{$this->selector}]";

        if (! empty($this->attributes)) {
            $message .= ' with the attributes '.json_encode($this->attributes);
        }

        return $message;
    }
}
