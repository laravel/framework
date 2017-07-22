<?php

namespace Illuminate\Translation;

use Illuminate\Support\Str;

class MessageSelector
{
    /**
     * Select a proper translation string based on the given number.
     *
     * @param  string $line
     * @param  int $number
     * @param  string $locale
     * @return mixed
     */
    public function choose($line, $number, $locale)
    {
        $segments = explode('|', $line);

        if (($value = $this->extract($segments, $number)) !== null) {
            return trim($value);
        }

        $segments = $this->stripConditions($segments);

        $pluralIndex = $this->getPluralIndex($locale, $number);

        if (count($segments) == 1 || !isset($segments[$pluralIndex])) {
            return $segments[0];
        }

        return $segments[$pluralIndex];
    }

    /**
     * Extract a translation string using inline conditions.
     *
     * @param  array $segments
     * @param  int $number
     * @return mixed
     */
    private function extract($segments, $number)
    {
        foreach ($segments as $part) {
            if (!is_null($line = $this->extractFromString($part, $number))) {
                return $line;
            }
        }
    }

    /**
     * Get the translation string if the condition matches.
     *
     * @param  string $part
     * @param  int $number
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
     * @param  array $segments
     * @return array
     */
    private function stripConditions($segments)
    {
        return collect($segments)->map(function ($part) {
            return preg_replace('/^[\{\[]([^\[\]\{\}]*)[\}\]]/', '', $part);
        })->all();
    }

    /**
     * Get the index to use for pluralization.
     *
     * The plural rules are derived from code of the Zend Framework (2010-09-25), which
     * is subject to the new BSD license (http://framework.zend.com/license/new-bsd)
     * Copyright (c) 2005-2010 - Zend Technologies USA Inc. (http://www.zend.com)
     *
     * @param  string $locale
     * @param  int $number
     * @return int
     */
    public function getPluralIndex($locale, $number)
    {
        $countryCode = strtok($locale, '_');
        switch (true) {
            case strpos('af|bn|bg|ca|da|de|el|en|eo|es|et|eu|fa|fi|fo|fur|fy', $countryCode) !== false:
            case strpos('gl|gu|ha|he|hu|is|it|ku|lb|ml|mn|mr|nah|nb|ne|nl|nn', $countryCode) !== false:
            case strpos('no|om|or|pa|pap|ps|pt|so|sq|sv|sw|ta|te|tk|ur|zu', $countryCode) !== false:
                return ($number == 1) ? 0 : 1;
            case strpos('az|bo|dz|id|ja|jv|ka|km|kn|ko|ms|th|tr|vi|zh', $countryCode) !== false:
                return 0;
            case strpos('am|bh|fil|fr|gun|hi|hy|ln|mg|nso|ti|wa|xbr', $countryCode) !== false:
                return (($number == 0) || ($number == 1)) ? 0 : 1;
            case strpos('be|bs|hr|ru|sr|uk', $countryCode) !== false:
                return (($number % 10 == 1) && ($number % 100 != 11)) ? 0 : ((($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 10) || ($number % 100 >= 20))) ? 1 : 2);
            case strpos('cs|sk', $countryCode) !== false:
                return ($number == 1) ? 0 : ((($number >= 2) && ($number <= 4)) ? 1 : 2);
            default :
                return $this->getUniquePluralIndex($countryCode, $number);
        }
    }

    /**
     * Get the index to use for countries with unique pluralization pattern
     *
     * @param string $countryCode
     * @param int $number
     * @return int
     */
    private function getUniquePluralIndex($countryCode, $number)
    {
        switch ($countryCode) {
            case 'ga':
                return ($number == 1) ? 0 : (($number == 2) ? 1 : 2);
            case 'lt':
                return (($number % 10 == 1) && ($number % 100 != 11)) ? 0 : ((($number % 10 >= 2) && (($number % 100 < 10) || ($number % 100 >= 20))) ? 1 : 2);
            case 'sl':
                return ($number % 100 == 1) ? 0 : (($number % 100 == 2) ? 1 : ((($number % 100 == 3) || ($number % 100 == 4)) ? 2 : 3));
            case 'mk':
                return ($number % 10 == 1) ? 0 : 1;
            case 'mt':
                return ($number == 1) ? 0 : ((($number == 0) || (($number % 100 > 1) && ($number % 100 < 11))) ? 1 : ((($number % 100 > 10) && ($number % 100 < 20)) ? 2 : 3));
            case 'lv':
                return ($number == 0) ? 0 : ((($number % 10 == 1) && ($number % 100 != 11)) ? 1 : 2);
            case 'pl':
                return ($number == 1) ? 0 : ((($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 12) || ($number % 100 > 14))) ? 1 : 2);
            case 'cy':
                return ($number == 1) ? 0 : (($number == 2) ? 1 : ((($number == 8) || ($number == 11)) ? 2 : 3));
            case 'ro':
                return ($number == 1) ? 0 : ((($number == 0) || (($number % 100 > 0) && ($number % 100 < 20))) ? 1 : 2);
            case 'ar':
                return ($number == 0) ? 0 : (($number == 1) ? 1 : (($number == 2) ? 2 : ((($number % 100 >= 3) && ($number % 100 <= 10)) ? 3 : ((($number % 100 >= 11) && ($number % 100 <= 99)) ? 4 : 5))));
            default:
                return 0;
        }
    }
}
