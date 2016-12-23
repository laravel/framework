<?php

namespace Illuminate\Translation;

use Illuminate\Support\Str;

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
        $segments = explode('|', $line);

        if (($value = $this->extract($segments, $number)) !== null) {
            return trim($value);
        }

        $segments = $this->stripConditions($segments);

        return count($segments) == 1 || $number == 1
                        ? $segments[0] : $segments[1];
    }

    /**
     * Extract a translation string using inline conditions.
     *
     * @param  array  $segments
     * @param  int  $number
     * @return mixed
     */
    private function extract($segments, $number)
    {
        foreach ($segments as $part) {
            if (! is_null($line = $this->extractFromString($part, $number))) {
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
     * Strip the inline conditions from each segment, just leaving the text.
     *
     * @param  array  $segments
     * @return array
     */
    private function stripConditions($segments)
    {
        return collect($segments)->map(function ($part) {
            return preg_replace('/^[\{\[]([^\[\]\{\}]*)[\}\]]/', '', $part);
        })->all();
    }
}
