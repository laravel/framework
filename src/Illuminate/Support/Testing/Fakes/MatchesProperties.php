<?php

namespace Illuminate\Support\Testing\Fakes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait MatchesProperties
{
    /**
     * @param  callable|array<string, mixed>|null  $callback
     * @return callable|null
     */
    protected function resolveTruthTest($callback)
    {
        if (! is_array($callback) || is_callable($callback)) {
            return $callback;
        }

        return fn ($object) => (new Collection($callback))->every(function ($expected, $property) use ($object) {
            if (! property_exists($object, $property) && ! isset($object->{$property})) {
                return false;
            }

            $actual = $object->{$property} ?? null;

            return $expected instanceof Model && $actual instanceof Model
                ? $expected->is($actual)
                : $actual === $expected;
        });
    }
}
