<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesIgnores
{
    /**
     * Compile Blade comments into an empty string.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileIgnores($value)
    {
        $pattern = sprintf('/%s(.*?)%s/s', '@ignore', '@endignore');

        preg_match_all($pattern, $value, $values);

        [$original, $contents] = $values;

        $contentsValues = [];

        foreach ($original as $key => $item) {
            $uniq = uniqid('ignore_');
            $contentsValues[$uniq] = $contents[$key];

            $value = str_replace($item, $uniq, $value);
        }

        return [$contentsValues, $value];
    }
}
