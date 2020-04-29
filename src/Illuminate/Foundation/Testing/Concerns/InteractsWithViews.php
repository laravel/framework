<?php

namespace Illuminate\Foundation\Testing\Concerns;

use Illuminate\Testing\TestView;

trait InteractsWithViews
{
    /**
     * Create a new TestView from the given view.
     *
     * @param  string  $view
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $data
     * @param  array  $mergeData
     * @return \Illuminate\Testing\TestView
     */
    protected function view($view, $data = [], $mergeData = [])
    {
        $view = view($view, $data, $mergeData);

        return new TestView($view);
    }
}
