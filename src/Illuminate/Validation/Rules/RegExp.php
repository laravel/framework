<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use Stringable;

class RegExp implements Stringable
{
    protected string $expression;
    protected bool $negated = false;

    /**
     * @var string[]
     */
    protected array $flags = [];

    /**
     * Create a new regex rule instance.
     *
     * @param  string  $expression
     * @param  string[]|\Illuminate\Contracts\Support\Arrayable  $extraFlags
     * @return void
     */
    public function __construct(string $expression, array|Arrayable $extraFlags = [])
    {
        $str = Str::of($expression);
        $currentFlags = str_split($str->afterLast('/'));

        if ($extraFlags instanceof Arrayable) {
            $extraFlags = $extraFlags->toArray();
        }

        $this->expression = $str->beforeLast('/')->append('/')->toString();
        $this->flags = array_unique([...$currentFlags, ...$extraFlags]);
    }

    /**
     * @param  string[]|\Illuminate\Contracts\Support\Arrayable  $flags
     */
    public function flags($flags = [])
    {
        $this->flags = match (true) {
            $flags instanceof Arrayable => $flags->toArray(),
            ! is_array($flags) => func_get_args(),
            default => $flags,
        };

        return $this;
    }

    public function flag(string $flag)
    {
        $this->flags = array_unique([...$this->flags, $flag]);

        return $this;
    }

    public function not()
    {
        $this->negated = true;

        return $this;
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf(
            '%s:%s%s',
            $this->negated ? 'not_regex' : 'regex',
            $this->expression,
            implode('', $this->flags),
        );
    }
}
