<?php

namespace Illuminate\Tests\App\ValueObjects;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;

class ValueObject implements Castable
{
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function castUsing(array $arguments)
    {
        return new class(...$arguments) implements CastsAttributes, SerializesCastableAttributes
        {
            private $argument;

            public function __construct($argument = null)
            {
                $this->argument = $argument;
            }

            public function get($model, $key, $value, $attributes)
            {
                if ($this->argument) {
                    return $this->argument;
                }

                return unserialize($value);
            }

            public function set($model, $key, $value, $attributes)
            {
                return serialize($value);
            }

            public function serialize($model, $key, $value, $attributes)
            {
                return serialize($value);
            }
        };
    }
}
