<?php

namespace Illuminate\Foundation\Testing\Constraints;

use PHPUnit_Framework_Constraint;
use Symfony\Component\DomCrawler\Crawler;
use SebastianBergmann\Comparator\ComparisonFailure;
use PHPUnit_Framework_ExpectationFailedException as FailedExpection;

abstract class PageConstraint extends PHPUnit_Framework_Constraint
{
    /**
     * Throws an exception for the given compared value and test description.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler|string  $crawler
     * @param  string  $description
     * @param  \SebastianBergmann\Comparator\ComparisonFailure|null  $comparisonFailure
     * @return void
     *
     * @throws \PHPUnit_Framework_ExpectationFailedException
     */
    protected function fail($crawler, $description, ComparisonFailure $comparisonFailure = null)
    {
        $html = $this->html($crawler);

        $failureDescription = sprintf(
            "%s\n\n\nFailed asserting that %s",
            $html, $this->getFailureDescription()
        );

        if (! empty($description)) {
            $failureDescription .= ": $description";
        }

        if (trim($html) != '') {
            $failureDescription .= '. Please check the content above.';
        } else {
            $failureDescription .= '. The response is empty.';
        }

        throw new FailedExpection($failureDescription, $comparisonFailure);
    }

    /**
     * Returns the description of the failure.
     *
     * @return string
     */
    protected function getFailureDescription()
    {
        return 'the page contains '.$this->toString();
    }

    /**
     * Returns a string representation of the object.
     *
     * This is just a placeholder to avoid having to define this method when it is not necessary.
     *
     * @return string
     */
    public function toString()
    {
        return '';
    }

    /**
     * Get the escaped text pattern.
     *
     * @param  string  $text
     * @return string
     */
    protected function getEscapedPattern($text)
    {
        $rawPattern = preg_quote($text, '/');

        $escapedPattern = preg_quote(e($text), '/');

        return $rawPattern == $escapedPattern
            ? $rawPattern : "({$rawPattern}|{$escapedPattern})";
    }

    /**
     * Make sure we are working with a crawler instead of the response string.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler|string  $crawler
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function crawler($crawler)
    {
        return is_object($crawler) ? $crawler : new Crawler($crawler);
    }

    /**
     * Make sure we obtain the HTML from the crawler or the response.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler|string  $crawler
     * @return string
     */
    protected function html($crawler)
    {
        return is_object($crawler) ? $crawler->html() : $crawler;
    }

    /**
     * Make sure we obtain the HTML from the crawler or the response.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler|string  $crawler
     * @return string
     */
    protected function text($crawler)
    {
        return is_object($crawler) ? $crawler->text() : strip_tags($crawler);
    }
}
