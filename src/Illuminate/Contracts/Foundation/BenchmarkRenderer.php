<?php

namespace Illuminate\Contracts\Foundation;

interface BenchmarkRenderer
{
    /**
     * Renders the benchmark results.
     *
     * @param  \Illuminate\Support\Collection<int, \Illuminate\Foundation\Benchmark\Result>  $results
     * @param  int  $repeats
     * @return never
     */
    public function render($results, $repeats);
}
