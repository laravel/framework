<?php

namespace Illuminate\Foundation\Console;

trait StubWriterTrait
{
    /**
     * Return a custom .stub file's path if one exists, if not, return default stub's path.
     *
     * @param  string  $custom
     * @param  string  $default
     * @return string
     */
    protected function stub($custom, $default)
    {
        $path = base_path('resources/stubs/'.$custom);

        if (file_exists($path)) {
            return $path;
        }

        return $default;
    }
}
