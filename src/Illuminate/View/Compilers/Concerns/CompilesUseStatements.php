<?php

namespace Illuminate\View\Compilers\Concerns;

trait CompilesUseStatements
{
    private const FUNCTION_MODIFIER_WITH_TRAILING_SPACE = 'function ';
    private const CONST_MODIFIER_WITH_TRAILING_SPACE = 'const ';

    /**
     * Compile the use statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileUse($expression)
    {
        $expression = trim(preg_replace('/[()]/', '', $expression), " '\"");

        // isolate alias
        if (str_contains($expression, '{')) {
            $pathWithOptionalModifier = $expression;
            $aliasWithLeadingSpace = '';
        } else {
            $segments = explode(',', $expression);
            $pathWithOptionalModifier = trim($segments[0], " '\"");
            $aliasWithLeadingSpace = isset($segments[1])
                ? ' as ' . trim($segments[1], " '\"")
                : '';
        }

        // split modifier and path
        if (str_starts_with($pathWithOptionalModifier, self::FUNCTION_MODIFIER_WITH_TRAILING_SPACE)) {
            $modifierWithTrailingSpace = self::FUNCTION_MODIFIER_WITH_TRAILING_SPACE;
            $path = explode(' ', $pathWithOptionalModifier, 2)[1] ?? $pathWithOptionalModifier;
        } elseif (str_starts_with($pathWithOptionalModifier, self::CONST_MODIFIER_WITH_TRAILING_SPACE)) {
            $modifierWithTrailingSpace = self::CONST_MODIFIER_WITH_TRAILING_SPACE;
            $path = explode(' ', $pathWithOptionalModifier, 2)[1] ?? $pathWithOptionalModifier;
        } else {
            $modifierWithTrailingSpace = '';
            $path = $pathWithOptionalModifier;
        }

        $path = ltrim($path, '\\');

        return "<?php use {$modifierWithTrailingSpace}\\{$path}{$aliasWithLeadingSpace}; ?>";
    }
}
