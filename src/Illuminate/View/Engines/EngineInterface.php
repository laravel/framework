<?php

namespace Illuminate\View\Engines;

interface EngineInterface
{
    /**
     * Get the evaluated contents of the view.
     *
     * @param  string  $path
     * @param  array   $data
     * @param  string  $view
     * @return string
     */
    public function get($path, array $data = [], $view = null);
}
