<?php

namespace Illuminate\Foundation\Testing\Constraints;

class ReversePageConstraint extends PageConstraint
{
    /**
     * The page constraint instance.
     *
     * @var \Illuminate\Foundation\Testing\Constraints\PageConstraint
     */
    protected $pageConstraint;

    /**
     * Create a new reverse page constraint instance.
     *
     * @param  \Illuminate\Foundation\Testing\Constraints\PageConstraint  $pageConstraint
     * @return void
     */
    public function __construct(PageConstraint $pageConstraint)
    {
        $this->pageConstraint = $pageConstraint;
    }

    /**
     * Reverse the original page constraint result.
     *
     * @param  \Symfony\Component\DomCrawler\Crawler  $crawler
     * @return bool
     */
    public function matches($crawler)
    {
        return ! $this->pageConstraint->matches($crawler);
    }

    /**
     * Get the description of the failure.
     *
     * This method will attempt to negate the original description.
     *
     * @return string
     */
    protected function getFailureDescription()
    {
        return str_replace(
            ['contains', 'is', 'has'],
            ['does not contain', 'is not', 'does not have'],
            $this->pageConstraint->getFailureDescription()
        );
    }

    /**
     * Get a string representation of the object.
     *
     * @return string
     */
    public function toString()
    {
        return $this->pageConstraint->toString();
    }
}
