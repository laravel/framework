<?php

namespace Illuminate\Translation;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class MessageSelector
{
    /**
     * Select a proper translation string based on the given number.
     *
     * @param  string  $line
     * @param  int  $number
     * @return mixed
     */
    public function choose($line, $number)
    {
        $parts = explode('|', $line);

        if (($value = $this->extract($parts, $number)) !== null) {
            return trim($value);
        }

        $parts = $this->stripConditions($parts);

        return count($parts) == 1 || $number == 1
                        ? $parts[0] : $parts[1];
    }

    /**
     * Extract a translation string using inline conditions.
     *
     * @param  array  $parts
     * @param  int  $number
     * @return mixed
     */
    private function extract($parts, $number)
    {
        foreach ($parts as $part) {
            if (($line = $this->extractFromString($part, $number)) !== null) {
                return $line;
            }
        }
    }

    /**
     * Get the translation string if the condition matches.
     *
     * @param  string  $part
     * @param  int  $number
     * @return mixed
     */
    private function extractFromString($part, $number)
    {
        preg_match('/^[\{\[]([^\[\]\{\}]*)[\}\]](.*)/s', $part, $matches);

        if (count($matches) != 3) {
            return;
        }

        $condition = $matches[1];

        $value = $matches[2];

        if (Str::contains($condition, ',')) {
            list($from, $to) = explode(',', $condition, 2);

            if ($to == '*' && $number >= $from) {
                return $value;
            } elseif ($from == '*' && $number <= $to) {
                return $value;
            } elseif ($number >= $from && $number <= $to) {
                return $value;
            }
        }

        return $condition == $number ? $value : null;
    }

    /**
     * Strip the inline condition.
     *
     * @param  array  $parts
     * @return array
     */
    private function stripConditions($parts)
    {
        return Collection::make($parts)->map(function ($part) {
            return preg_replace('/^[\{\[]([^\[\]\{\}]*)[\}\]]/', '', $part);
        })->toArray();
    }
}
