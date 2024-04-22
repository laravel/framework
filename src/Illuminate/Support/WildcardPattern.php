<?php

namespace Illuminate\Support;

class WildcardPattern
{
    /**
     * Saved regex pattern for provided wildcard.
     *
     * @var string
     */
    protected string $regexPattern;

    /**
     * Create a new wildcard pattern instance.
     *
     * @param  string  $pattern
     */
    public function __construct(public readonly string $pattern)
    {
        $this->regexPattern = self::createRegexPatternForWildcard($pattern);
    }

    /**
     * Determine if provided value matches wildcard pattern.
     *
     * @param  $value
     * @return bool
     */
    public function matches($value): bool
    {
        return preg_match($this->regexPattern, (string) $value) === 1;
    }

    /**
     * Creates regex pattern for provided wildcard pattern.
     *
     * @param  string  $pattern
     * @return string
     */
    public static function createRegexPatternForWildcard(string $pattern): string
    {
        $pattern = preg_quote($pattern, '#');

        // Asterisks are translated into zero-or-more regular expression wildcards
        // to make it convenient to check if the strings starts with the given
        // pattern such as "library/*", making any string check convenient.
        $pattern = str_replace('\*', '.*', $pattern);

        return '#^'.$pattern.'\z#u';
    }
}
