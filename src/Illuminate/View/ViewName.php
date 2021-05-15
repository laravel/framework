<?php

namespace Illuminate\View;

use Illuminate\Support\Str;

class ViewName
{
    /**
     * Normalize the given view name.
     *
     * @param  string  $name
     * @return string
     */
    public static function normalize($name)
    {
        $delimiter = ViewFinderInterface::HINT_PATH_DELIMITER;

        if (Str::contains($name, $delimiter) === false) {
            return Str::replace('/', '.', $name);
        }

        [$namespace, $name] = explode($delimiter, $name);

        return $namespace.$delimiter.SupportString::replace('/', '.', $name);
    }
}
