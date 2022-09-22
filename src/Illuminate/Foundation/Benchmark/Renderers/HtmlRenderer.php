<?php

namespace Illuminate\Foundation\Benchmark\Renderers;

use Illuminate\Contracts\Foundation\BenchmarkRenderer;
use Illuminate\Foundation\Http\HtmlDumper;

class HtmlRenderer implements BenchmarkRenderer
{
    use Concerns\InspectsClosures, Concerns\Terminatable;

    /**
     * The callable used to dump the results in the browser.
     *
     * @var (callable(): void)|null
     */
    protected static $dumpUsing;

    /**
     * {@inheritdoc}
     */
    public function render($results, $repeats)
    {
        $results = $results->mapWithKeys(function ($result, $index) use ($results) {
            if (! is_string($key = $result->key)) {
                $key = $this->getCodeDescription($result->callback);
            }

            if ($results->count() > 1) {
                $key = sprintf('[%s] %s', $index + 1, $key);
            }

            return [$key => number_format($result->average * 1000, 3).'ms'];
        });

        HtmlDumper::resolveDumpSourceUsing(fn () => null);

        try {
            (static::$dumpUsing ?: 'dump')($results->prepend($repeats, 'repeats')->toArray());
        } finally {
            HtmlDumper::resolveDumpSourceUsing(null);
        }

        $this->terminate();
    }

    /**
     * Sets the callable used to dump the results in the browser.
     *
     * @param  (callable(): void)|null  $callback
     * @return void
     */
    public static function dumpUsing($callback)
    {
        static::$dumpUsing = $callback;
    }
}
