<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Str;
use Illuminate\Contracts\Support\Stringable as SupportStringable;
use Stringable;

class RegExp implements Stringable
{
    protected string $regExp;
    protected bool $negated = false;

    /**
     * @var string[]
     */
    protected array $flags = [];

    /**
     * Create a new array rule instance.
     *
     * @param  string  $regExp
     * @param  string[]|\Illuminate\Contracts\Support\Arrayable|null  $flags
     * @return void
     */
    public function __construct(string $regExp, $flags = null)
    {
        $this->regExp = $regExp;
        $this->flags(...$flags);
    }

    public function active(bool $active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @param  string[]|\Illuminate\Contracts\Support\Arrayable|null  $flags
     */
    public function flags($flags)
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
        return (string) Str::of($this->not ? 'not_regex' : 'regex')
            ->append(
                ':',
                $this->regExp,
            )
            ->when($this->flags, function (SupportStringable $str) {
                $end = $str->afterLast('/');
                $flags = explode('', $end);
                $flags = array_unique([...$this->flags, $flags]);

                return $str->replaceEnd('/'.$end, '/'.implode('', $flags));
            });
    }
}
