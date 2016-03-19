<?php

namespace Illuminate\Foundation\Testing\Constraints;

use Symfony\Component\DomCrawler\Crawler;

abstract class FormFieldConstraint extends PageConstraint
{
    /**
     * Name or ID of the element.
     *
     * @var string
     */
    protected $selector;

    /**
     * Expected value.
     *
     * @var string
     */
    protected $value;

    /**
     * Create a new form field constraint instance.
     *
     * @param  string  $selector  element's name or ID
     * @param  mixed  $value  expected value
     * @return void
     */
    public function __construct($selector, $value)
    {
        $this->selector = $selector;
        $this->value = (string) $value;
    }

    /**
     * Get the valid elements.
     *
     * Multiple elements should be separated by commas without spaces.
     *
     * @return string
     */
    abstract protected function validElements();

    /**
     * Get the form field.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $name
     * @return \Symfony\Component\DomCrawler\Crawler
     *
     * @throws \PHPUnit_Framework_ExpectationFailedException
     */
    protected function field(Crawler $crawler)
    {
        $name = str_replace('#', '', $this->selector);

        $id = str_replace(['[', ']'], ['\\[', '\\]'], $name);

        $elements = explode(',', $this->validElements());

        array_walk($elements, function (&$element) use ($name, $id) {
            $element = "{$element}#{$id}, {$element}[name='{$name}']";
        });

        $field = $crawler->filter(implode(', ', $elements));

        if ($field->count() > 0) {
            return $field;
        }

        $description = sprintf(
            'There is no %s with the name or ID [%s]',
            $this->validElements(), $this->selector
        );

        $this->fail($crawler, $description);
    }
}
