<?php

namespace Illuminate\Foundation\VarDumper;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Str;
use stdClass;
use Symfony\Component\VarDumper\Caster\Caster;
use Symfony\Component\VarDumper\Caster\CutStub;
use Symfony\Component\VarDumper\Cloner\Stub;

class Properties extends Collection
{
    /**
     * Known casting prefixes.
     *
     * @var array
     */
    protected $prefixes = [
        Caster::PREFIX_PROTECTED,
        Caster::PREFIX_VIRTUAL,
        Caster::PREFIX_DYNAMIC,
    ];

    /**
     * Update the stub's number of "cut" items by comparing to original properties.
     *
     * @param  \Symfony\Component\VarDumper\Cloner\Stub  $stub
     * @param  \Illuminate\Foundation\VarDumper\Properties  $original
     * @return $this
     */
    public function applyCutsToStub($stub, $original)
    {
        $stub->cut += ($original->count() - $this->count());

        return $this;
    }

    /**
     * Cut a property out.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return \Symfony\Component\VarDumper\Caster\CutStub
     */
    public function cut($key, $default = null): CutStub
    {
        return new CutStub($this->get($key, $default));
    }

    /**
     * Cut a protected property out.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return \Symfony\Component\VarDumper\Caster\CutStub
     */
    public function cutProtected($key, $default = null): CutStub
    {
        return $this->cut(Key::protected($key), $default);
    }

    /**
     * Cut a virtual property out.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return \Symfony\Component\VarDumper\Caster\CutStub
     */
    public function cutVirtual($key, $default = null): CutStub
    {
        return $this->cut(Key::virtual($key), $default);
    }

    /**
     * Cut a dynamic property out.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return \Symfony\Component\VarDumper\Caster\CutStub
     */
    public function cutDynamic($key, $default = null): CutStub
    {
        return $this->cut(Key::dynamic($key), $default);
    }

    /**
     * Get a property value.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $missing = new stdClass();
        foreach ($this->addPrefixes($key) as $prefixed_key) {
            $parameter = parent::get($prefixed_key, $missing);
            if ($missing !== $parameter) {
                return $parameter;
            }
        }

        return $default;
    }

    /**
     * Get a protected property value.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getProtected($key, $default = null)
    {
        return $this->get(Key::protected($key), $default);
    }

    /**
     * Get a virtual property value.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getVirtual($key, $default = null)
    {
        return $this->get(Key::virtual($key), $default);
    }

    /**
     * Get a dynamic property value.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getDynamic($key, $default = null)
    {
        return $this->get(Key::dynamic($key), $default);
    }

    /**
     * Check whether a property/properties exists.
     *
     * @param  string|array  $key
     * @return bool
     */
    public function has($key)
    {
        if (! is_array($key)) {
            $key = func_get_args();
        }

        foreach ($key as $value) {
            if (! $this->hasAny($this->addPrefixes($value))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check whether any provided property/properties exists.
     *
     * @param  string|array  $key
     * @return bool
     */
    public function hasAny($key)
    {
        if ($this->isEmpty()) {
            return false;
        }

        if (! is_array($key)) {
            $key = func_get_args();
        }

        foreach ($key as $value) {
            if (array_key_exists($value, $this->items)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add a protected property.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function putProtected($key, $value)
    {
        return $this->put(Key::protected($key), $value);
    }

    /**
     * Add a virtual property.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function putVirtual($key, $value)
    {
        return $this->put(Key::virtual($key), $value);
    }

    /**
     * Add a dynamic property.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function putDynamic($key, $value)
    {
        return $this->put(Key::dynamic($key), $value);
    }

    /**
     * Copy a property from one collection to this.
     *
     * @param  string  $key
     * @param  \Illuminate\Foundation\VarDumper\Properties  $from
     * @param  mixed  $default
     * @return $this
     */
    public function copy($key, $from, $default = null)
    {
        return $this->put($key, $from->get($key, $default));
    }

    /**
     * Copy a protected property from one collection to this.
     *
     * @param  string  $key
     * @param  \Illuminate\Foundation\VarDumper\Properties  $from
     * @param  mixed  $default
     * @return $this
     */
    public function copyProtected($key, $from, $default = null)
    {
        return $this->copy(Key::protected($key), $from, $default);
    }

    /**
     * Copy a virtual property from one collection to this.
     *
     * @param  string  $key
     * @param  \Illuminate\Foundation\VarDumper\Properties  $from
     * @param  mixed  $default
     * @return $this
     */
    public function copyVirtual($key, $from, $default = null)
    {
        return $this->copy(Key::virtual($key), $from, $default);
    }

    /**
     * Copy a dynamic property from one collection to this.
     *
     * @param  string  $key
     * @param  \Illuminate\Foundation\VarDumper\Properties  $from
     * @param  mixed  $default
     * @return $this
     */
    public function copyDynamic($key, $from, $default = null)
    {
        return $this->copy(Key::dynamic($key), $from, $default);
    }

    /**
     * Cut a property from one collection and copy it to this.
     *
     * @param  string  $key
     * @param  \Illuminate\Foundation\VarDumper\Properties  $from
     * @param  mixed  $default
     * @return $this
     */
    public function copyAndCut($key, $from, $default = null)
    {
        return $this->put($key, $from->cut($key, $default));
    }

    /**
     * Cut a protected property from one collection and copy it to this.
     *
     * @param  string  $key
     * @param  \Illuminate\Foundation\VarDumper\Properties  $from
     * @param  mixed  $default
     * @return $this
     */
    public function copyAndCutProtected($key, $from, $default = null)
    {
        return $this->copyAndCut(Key::protected($key), $from, $default);
    }

    /**
     * Cut a virtual property from one collection and copy it to this.
     *
     * @param  string  $key
     * @param  \Illuminate\Foundation\VarDumper\Properties  $from
     * @param  mixed  $default
     * @return $this
     */
    public function copyAndCutVirtual($key, $from, $default = null)
    {
        return $this->copyAndCut(Key::virtual($key), $from, $default);
    }

    /**
     * Cut a dynamic property from one collection and copy it to this.
     *
     * @param  string  $key
     * @param  \Illuminate\Foundation\VarDumper\Properties  $from
     * @param  mixed  $default
     * @return $this
     */
    public function copyAndCutDynamic($key, $from, $default = null)
    {
        return $this->copyAndCut(Key::dynamic($key), $from, $default);
    }

    /**
     * Get only the specified properties.
     *
     * @param  string|array  $keys
     * @return \Illuminate\Foundation\VarDumper\Properties
     */
    public function only($keys)
    {
        return $this->filter(function ($value, $key) use ($keys) {
            return Str::is($keys, $key) || Str::is($keys, $this->stripPrefix($key));
        });
    }

    /**
     * Get all properties except the specified ones.
     *
     * @param  string|array  $keys
     * @return \Illuminate\Foundation\VarDumper\Properties
     */
    public function except($keys)
    {
        return $this->reject(function ($value, $key) use ($keys) {
            return Str::is($keys, $key) || Str::is($keys, $this->stripPrefix($key));
        });
    }

    /**
     * Filter the properties. If no callback is provided, empty properties are cut.
     *
     * @param  callable|null  $callback
     * @return \Illuminate\Foundation\VarDumper\Properties
     */
    public function filter(callable $callback = null)
    {
        if (null === $callback) {
            $callback = static function ($property) {
                if (is_array($property)) {
                    return count($property);
                }

                if ($property instanceof Enumerable) {
                    return $property->isNotEmpty();
                }

                return null !== $property;
            };
        }

        return parent::filter($callback);
    }

    /**
     * Reorder the properties by a set of rules.
     *
     * @param  array  $rules
     * @return \Illuminate\Foundation\VarDumper\Properties
     */
    public function reorder(array $rules)
    {
        return $this->sortBy($this->getReorderCallback($rules));
    }

    /**
     * Convert sorting rules to a 'sortBy' callback function.
     *
     * @param  array  $rules
     * @return \Closure
     */
    protected function getReorderCallback(array $rules)
    {
        $map = $this->createReorderMapFromRules($rules);

        return function ($value, $key) use ($map) {
            $result = Arr::pull($map, '*');

            foreach ($map as $pattern => $position) {
                if ($key === $pattern || Str::is($pattern, $this->stripPrefix($key))) {
                    $result = $position;
                }
            }

            return $result;
        };
    }

    /**
     * Build a map of patterns to sort position for reordering.
     *
     * @param  array  $rules
     * @return array
     */
    protected function createReorderMapFromRules(array $rules): array
    {
        $rules = array_values($rules);
        $map = array_combine($rules, array_keys($rules));

        // Ensure that there's always a '*' pattern, defaulting to the end
        $map['*'] ??= count($map);

        return $map;
    }

    /**
     * Strip any VarCloner prefixes from a key.
     *
     * @param  string  $key
     * @return string
     */
    protected function stripPrefix($key)
    {
        return str_replace($this->prefixes, '', $key);
    }

    /**
     * Get all possible matching prefixed keys for comparison.
     *
     * @param  string  $key
     * @return array
     */
    protected function addPrefixes($key)
    {
        if (Str::startsWith($key, $this->prefixes)) {
            return [$key];
        }

        return array_merge([$key], array_map(fn ($prefix) => $prefix.$key, $this->prefixes));
    }
}
