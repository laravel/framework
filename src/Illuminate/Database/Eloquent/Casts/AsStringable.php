<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class AsStringable implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<\Illuminate\Support\Stringable, string|\Stringable>
     */
    public static function castUsing(array $arguments)
    {
        return new class($arguments) implements CastsAttributes
        {
            protected bool $forceInstance;

            public function __construct(array $arguments)
            {
                $this->forceInstance = ($arguments[0] ?? '') === 'force';
            }

            public function get($model, $key, $value, $attributes)
            {
                if (! isset($value)) {
                    return $this->defaultValue();
                }

                return Str::of($value);
            }

            public function set($model, $key, $value, $attributes)
            {
                return isset($value) ? (string) $value : null;
            }

            protected function defaultValue(): ?Stringable
            {
                return $this->forceInstance ? new Stringable : null;
            }
        };
    }

    /**
     * Always get a Stringable instance.
     */
    public static function force(): string
    {
        return static::class.':force';
    }
}
