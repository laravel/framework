<?php

namespace Illuminate\Support\Traits;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Stringable;

trait ToonCast
{
    /**
     * Convert the object to its TOON representation.
     *
     * @return string
     */
    public function toToon(): string
    {
        $data = match (true) {
            $this instanceof Arrayable => $this->toArray(),
            $this instanceof Jsonable => json_decode($this->toJson(), true),
            default => (array) $this,
        };

        return $this->convertToToon($data);
    }

    /**
     * Convert array data to TOON format.
     *
     * @param  array  $data
     * @param  int  $indent
     * @return string
     */
    protected function convertToToon(array $data, int $indent = 0): string
    {
        $result = [];
        $prefix = str_repeat('  ', $indent);

        foreach ($data as $key => $value) {
            if (! is_array($value)) {
                $result[] = $prefix . $key . ': ' . $this->formatValue($value);

                continue;
            }

            if ($value === []) {
                $result[] = $prefix . $key . ': []';

                continue;
            }

            if ($this->isAssociativeArray($value)) {
                $result[] = $prefix . $key . ':';
                $result[] = $this->convertToToon($value, $indent + 1);

                continue;
            }

            if ($this->isArrayOfObjects($value)) {
                $keys = array_keys($value[0]);
                $count = count($value);
                $result[] = $prefix . $key . '[' . $count . ']{' . implode(',', $keys) . '}:';

                foreach ($value as $item) {
                    $values = array_map(fn ($k) => $this->formatValue($item[$k]), $keys);
                    $result[] = $prefix . '  ' . implode(',', $values);
                }

                continue;
            }

            $count = count($value);
            $formattedValues = array_map([$this, 'formatValue'], $value);
            $result[] = $prefix . $key . '[' . $count . ']: ' . implode(',', $formattedValues);
        }

        return implode("\n", array_filter($result));
    }

    /**
     * Check if array is associative.
     *
     * @param  array  $array
     * @return bool
     */
    protected function isAssociativeArray(array $array): bool
    {
        return $array === []
            ? false
            : array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Check if array contains objects (associative arrays).
     *
     * @param  array  $array
     * @return bool
     */
    protected function isArrayOfObjects(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        foreach ($array as $item) {
            if (! is_array($item) || ! $this->isAssociativeArray($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Format value for TOON output.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function formatValue(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_null($value) => 'null',
            is_string($value) => $value,
            $value instanceof Stringable => $value->toString(),
            default => (string) $value,
        };
    }
}
