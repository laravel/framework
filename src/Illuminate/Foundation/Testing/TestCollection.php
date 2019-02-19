<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert as PHPUnit;
use SebastianBergmann\Exporter\Exporter;
use Illuminate\Pagination\AbstractPaginator;

class TestCollection
{
    use FallbackToParent;

    /**
     * The collection to test.
     *
     * @var \Illuminate\Support\Collection|\Illuminate\Pagination\AbstractPaginator
     */
    protected $collection;

    /**
     * Create a new test collection instance.
     *
     * @param  mixed  $collection
     * @param null $fallback
     * @return void
     */
    public function __construct($collection, $fallback = null)
    {
        if (! ($collection instanceof Collection || $collection instanceof AbstractPaginator)) {
            $this->fail('"%s" is not a collection', $collection);
        }

        $this->collection = $collection;
        $this->setFallback($fallback);
    }

    /**
     * Assert the collection contains the expected value.
     *
     * @param  mixed  $expected
     * @return $this
     */
    public function contains($expected)
    {
        if (! $this->collection->contains($expected)) {
            $this->fail('The collection does not contain the expected value "%s"', $expected);
        }

        return $this;
    }

    /**
     * Assert the collection does not contain the unexpected value.
     *
     * @param  mixed  $expected
     * @return $this
     */
    public function notContains($expected)
    {
        if ($this->collection->contains($expected)) {
            $this->fail('The collection contains the unexpected value "%s"', $expected);
        }

        return $this;
    }

    /**
     * Assert the collection has the expected amount of elements.
     *
     * @param  int  $expected
     * @return $this
     */
    public function counts(int $expected)
    {
        $count = $this->collection->count();

        if ($count !== $expected) {
            $this->fail('The collection does not contain %s elements (%s found instead)', $expected, $count);
        }

        return $this;
    }

    /**
     * Fails the test with the given message.
     *
     * @param  string  $message
     * @param  mixed  $expected
     * @param  mixed|null  $found
     *
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    protected function fail($message, $expected, $found = null)
    {
        PHPUnit::fail(sprintf($message, $this->export($expected), $this->export($found)));
    }

    /**
     * Exports a value into a single-line string
     *
     * @param  mixed  $value
     * @return string
     */
    protected function export($value)
    {
        return (new Exporter)->shortenedExport($value);
    }
}
