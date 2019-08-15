<?php

namespace Illuminate\Translation;

use Illuminate\Support\Str;

class MessageSelector
{
    /**
     * Select a proper translation string based on the given number.
     *
     * @param  string  $line
     * @param  mixed  $number
     * @param  string  $locale
     * @return string
     */
    public function choose($line, $number, $locale)
    {
        $segments = explode('|', $line);

        if (($value = $this->extract($segments, $number)) !== null) {
            return trim($value);
        }

        $segments = $this->stripConditions($segments);

        $pluralIndex = $this->getPluralIndex($locale, $number);

        if (count($segments) === 1 || ! isset($segments[$pluralIndex])) {
            return $segments[0];
        }

        return $segments[$pluralIndex];
    }

    /**
     * Extract a translation string using inline conditions.
     *
     * @param  array  $segments
     * @param  mixed  $number
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
     * @param  mixed  $number
     * @return mixed
     */
    private function extractFromString($part, $number)
    {
        preg_match('/^[\{\[]([^\[\]\{\}]*)[\}\]](.*)/s', $part, $matches);

        if (count($matches) !== 3) {
            return;
        }

        $condition = $matches[1];

        $value = $matches[2];

        if (Str::contains($condition, ',')) {
            [$from, $to] = explode(',', $condition, 2);

            if ($to === '*' && $number >= $from) {
                return $value;
            } elseif ($from === '*' && $number <= $to) {
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

    /**
     * Get the index to use for pluralization.
     *
     * @see https://www.unicode.org/cldr/charts/35/supplemental/language_plural_rules.html
     *
     * @param  string  $locale
     * @param  mixed  $number
     * @return int
     */
    public function getPluralIndex($locale, $number)
    {
        if ($locale !== 'pt_PT') {
            $locale = explode('_', $locale)[0];
        }

        $dc = new DecimalQuantity($number);

        switch ($locale) {
            // other
            case 'bm':
            case 'bo':
            case 'dz':
            case 'id':
            case 'ig':
            case 'ii':
            case 'in':
            case 'ja':
            case 'jbo':
            case 'jv':
            case 'jw':
            case 'kde':
            case 'kea':
            case 'km':
            case 'ko':
            case 'lkt':
            case 'lo':
            case 'ms':
            case 'my':
            case 'nqo':
            case 'sah':
            case 'ses':
            case 'sg':
            case 'th':
            case 'to':
            case 'vi':
            case 'wo':
            case 'yo':
            case 'yue':
            case 'zh':
                return 0;

            // one, other
            case 'am':
            case 'as':
            case 'bn':
            case 'fa':
            case 'gu':
            case 'hi':
            case 'kn':
            case 'zu':
                $n = $dc->getPluralOperand('n');
                $i = $dc->getPluralOperand('i');

                return ($i == 0 || $n == 1) ? 0 : 1;

            // one, other
            case 'ff':
            case 'fr':
            case 'hy':
            case 'kab':
            case 'pt':
            case 'pt_BR':
                $i = $dc->getPluralOperand('i');

                return ($i == 0 || $i == 1) ? 0 : 1;

            // one, other
            case 'ast':
            case 'ca':
            case 'de':
            case 'en':
            case 'et':
            case 'fi':
            case 'fy':
            case 'gl':
            case 'ia':
            case 'io':
            case 'it':
            case 'ji':
            case 'nl':
            case 'pt_PT':
            case 'sc':
            case 'scn':
            case 'sv':
            case 'sw':
            case 'ur':
            case 'yi':
                $i = $dc->getPluralOperand('i');
                $v = $dc->getPluralOperand('v');

                return ($i == 1 && $v == 0) ? 0 : 1;

            // one, other
            case 'si':
                $n = $dc->getPluralOperand('n');
                $i = $dc->getPluralOperand('i');
                $f = $dc->getPluralOperand('f');

                return (($n == 0 || $n == 1) || ($i == 0 && $f == 1)) ? 0 : 1;

            // one, other
            case 'ak':
            case 'bh':
            case 'guw':
            case 'ln':
            case 'mg':
            case 'nso':
            case 'pa':
            case 'ti':
            case 'wa':
                $n = $dc->getPluralOperand('n');

                return ($n == 0 || $n == 1) ? 0 : 1;

            // one, other
            case 'tzm':
                $n = $dc->getPluralOperand('n');
                $i = $dc->getPluralOperand('i');
                $v = $dc->getPluralOperand('v');

                return (($n == 0 || $n == 1) || ($v == 0 && $i >= 11 && $i <= 99)) ? 0 : 1;

            // one, other
            case 'af':
            case 'asa':
            case 'az':
            case 'bem':
            case 'bez':
            case 'bg':
            case 'brx':
            case 'ce':
            case 'cgg':
            case 'chr':
            case 'ckb':
            case 'dv':
            case 'ee':
            case 'el':
            case 'eo':
            case 'es':
            case 'eu':
            case 'fo':
            case 'fur':
            case 'gsw':
            case 'ha':
            case 'haw':
            case 'hu':
            case 'jgo':
            case 'jmc':
            case 'ka':
            case 'kaj':
            case 'kcg':
            case 'kk':
            case 'kkj':
            case 'kl':
            case 'ks':
            case 'ksb':
            case 'ku':
            case 'ky':
            case 'lb':
            case 'lg':
            case 'mas':
            case 'mgo':
            case 'ml':
            case 'mn':
            case 'mr':
            case 'nah':
            case 'nb':
            case 'nd':
            case 'ne':
            case 'nn':
            case 'nnh':
            case 'no':
            case 'nr':
            case 'ny':
            case 'nyn':
            case 'om':
            case 'or':
            case 'os':
            case 'pap':
            case 'ps':
            case 'rm':
            case 'rof':
            case 'rwk':
            case 'saq':
            case 'sd':
            case 'sdh':
            case 'seh':
            case 'sn':
            case 'so':
            case 'sq':
            case 'ss':
            case 'ssy':
            case 'st':
            case 'syr':
            case 'ta':
            case 'te':
            case 'teo':
            case 'tig':
            case 'tk':
            case 'tn':
            case 'tr':
            case 'ts':
            case 'ug':
            case 'uz':
            case 've':
            case 'vo':
            case 'vun':
            case 'wae':
            case 'xh':
            case 'xog':
                $n = $dc->getPluralOperand('n');

                return ($n == 1) ? 0 : 1;

            // one, other
            case 'da':
                $n = $dc->getPluralOperand('n');
                $i = $dc->getPluralOperand('i');
                $t = $dc->getPluralOperand('t');

                return (($n == 1 || $t != 0) && ($i == 0 || $i == 1)) ? 0 : 1;

            // one, other
            case 'is':
                $i = $dc->getPluralOperand('i');
                $t = $dc->getPluralOperand('t');

                return (($t == 0 && $i % 10 == 1 && $i % 100 != 11) || $t != 0) ? 0 : 1;

            // one, other
            case 'mk':
                $i = $dc->getPluralOperand('i');
                $v = $dc->getPluralOperand('v');
                $f = $dc->getPluralOperand('f');
                $t = $dc->getPluralOperand('t');

                return (($v == 0 && $i % 10 == 1 && $i % 100 != 11) || ($f % 10 == 1 && $f % 100 != 11)) ? 0 : 1;

            // one, other
            case 'ceb':
            case 'fil':
            case 'tl':
                $i = $dc->getPluralOperand('i');
                $v = $dc->getPluralOperand('v');
                $f = $dc->getPluralOperand('f');

                return (($v == 0 && $i >= 1 && $i <= 3) || ($v == 0 && ! in_array($i % 10, [4, 6, 9])) || ($v != 0 && ! in_array($f % 10, [4, 6, 9]))) ? 0 : 1;

            // zero, one, other
            case 'lv':
            case 'prg':
                $n = $dc->getPluralOperand('n');
                $v = $dc->getPluralOperand('v');
                $f = $dc->getPluralOperand('f');

                $n10 = fmod($n, 10);
                $n100 = fmod($n, 100);
                $f10 = $f % 10;
                $f100 = $f % 100;

                return (($n10 == 0 || ($n100 >= 11 && $n100 <= 19)) || ($v == 2 && $f100 >= 11 && $f100 <= 19)) ? 0 : ((($n10 == 1 && $n100 != 11) || ($v == 2 && $f10 == 1 && $f100 != 11) || ($v != 2 && $f10 == 1)) ? 1 : 2);

            // zero, one, other
            case 'lag':
                $n = $dc->getPluralOperand('n');
                $i = $dc->getPluralOperand('i');

                return ($n == 0) ? 0 : ((($i == 0 || $i == 1) && $n != 0) ? 1 : 2);

            // zero, one, other
            case 'ksh':
                $n = $dc->getPluralOperand('n');

                return ($n == 0) ? 0 : (($n == 1) ? 1 : 2);

            // one, two, other
            case 'iu':
            case 'naq':
            case 'se':
            case 'sma':
            case 'smi':
            case 'smj':
            case 'smn':
            case 'sms':
                $n = $dc->getPluralOperand('n');

                return ($n == 1) ? 0 : (($n == 2) ? 1 : 2);

            // one, few, other
            case 'shi':
                $n = $dc->getPluralOperand('n');
                $i = $dc->getPluralOperand('i');
                $f = $dc->getPluralOperand('f');

                return ($i == 0 || $n == 1) ? 0 : (($f == 0 && $n >= 2 && $n <= 10) ? 1 : 2);

            // one, few, other
            case 'mo':
            case 'ro':
                $n = $dc->getPluralOperand('n');
                $i = $dc->getPluralOperand('i');
                $v = $dc->getPluralOperand('v');

                $n100 = fmod($n, 100);

                return ($i == 1 && $v == 0) ? 0 : (($v != 0 || $n == 0 || ($n100 >= 2 && $n100 <= 19)) ? 1 : 2);

            // one, few, other
            case 'bs':
            case 'hr':
            case 'sh':
            case 'sr':
                $i = $dc->getPluralOperand('i');
                $v = $dc->getPluralOperand('v');
                $f = $dc->getPluralOperand('f');

                $i10 = $i % 10;
                $i100 = $i % 100;
                $f10 = $f % 10;
                $f100 = $f % 100;

                return (($v == 0 && $i10 == 1 && $i100 != 11) || ($f10 == 1 && $f100 != 11)) ? 0 : ((($v == 0 && $i10 >= 2 && $i10 <= 4 && ! ($i100 >= 12 && $i100 <= 14)) || ($f10 >= 2 && $f10 <= 4 && ! ($f100 >= 12 && $f100 <= 14))) ? 1 : 2);

            // one, two, few, other
            case 'gd':
                $n = $dc->getPluralOperand('n');
                $f = $dc->getPluralOperand('f');

                return ($n == 1 || $n == 11) ? 0 : (($n == 2 || $n == 12) ? 1 : (($f == 0 && (($n >= 3 && $n <= 10) || ($n >= 13 && $n <= 19))) ? 2 : 3));

            // one, two, few, other
            case 'sl':
                $n = $dc->getPluralOperand('n');
                $i = $dc->getPluralOperand('i');
                $v = $dc->getPluralOperand('v');

                $i100 = $i % 100;

                return ($v == 0 && $i100 == 1) ? 0 : (($v == 0 && $i100 == 2) ? 1 : ((($v == 0 && $i100 >= 3 && $i100 <= 4) || $v != 0) ? 2 : 3));

            // one, two, few, other
            case 'dsb':
            case 'hsb':
                $i = $dc->getPluralOperand('i');
                $v = $dc->getPluralOperand('v');
                $f = $dc->getPluralOperand('f');

                $i100 = $i % 100;
                $f100 = $f % 100;

                return (($v == 0 && $i100 == 1) || $f100 == 1) ? 0 : ((($v == 0 && $i100 == 2) || $f100 == 2) ? 1 : ((($v == 0 && $i100 >= 3 && $i100 <= 4) || ($f100 >= 3 && $f100 <= 4)) ? 2 : 3));

            // one, two, many, other
            case 'he':
            case 'iw':
                $n = $dc->getPluralOperand('n');
                $i = $dc->getPluralOperand('i');
                $v = $dc->getPluralOperand('v');

                return ($i == 1 && $v == 0) ? 0 : (($i == 2 && $v == 0) ? 1 : (($v == 0 & $n > 10 && fmod($n, 10) == 0) ? 2 : 3));

            // one, few, many, other
            case 'cs':
            case 'sk':
                $i = $dc->getPluralOperand('i');
                $v = $dc->getPluralOperand('v');

                return ($i == 1 && $v == 0) ? 0 : (($i >= 2 && $i <= 4 && $v == 0) ? 1 : (($v != 0) ? 2 : 3));

            // one, few, many, other
            case 'pl':
                $i = $dc->getPluralOperand('i');
                $v = $dc->getPluralOperand('v');

                $i10 = $i % 10;
                $i100 = $i % 100;

                return ($i == 1 && $v == 0) ? 0 : (($v == 0 && $i10 >= 2 && $i10 <= 4 && ! ($i100 >= 12 && $i100 <= 14)) ? 1 : ((($v == 0 && $i != 1 && $i10 >= 0 && $i10 <= 1) || ($v == 0 && $i10 >= 5 && $i10 <= 9) || ($v == 0 && $i100 >= 12 && $i100 <= 14)) ? 2 : 3));

            // one, few, many, other
            case 'be':
                $n = $dc->getPluralOperand('n');

                $n10 = fmod($n, 10);
                $n100 = fmod($n, 100);

                return ($n10 == 1 && $n100 != 11) ? 0 : (($n10 >= 2 && $n10 <= 4 && ($n100 < 12 || $n100 > 14)) ? 1 : (($n10 == 0 || ($n10 >= 5 && $n10 <= 9) || ($n100 >= 11 && $n100 <= 14)) ? 2 : 3));

            // one, few, many, other
            case 'lt':
                $n = $dc->getPluralOperand('n');
                $f = $dc->getPluralOperand('f');

                $n10 = fmod($n, 10);
                $n100 = fmod($n, 100);

                return ($n10 == 1 && ! ($n100 >= 11 && $n100 <= 19)) ? 0 : (($n10 >= 2 && $n10 <= 9 && ! ($n100 >= 11 && $n100 <= 19)) ? 1 : (($f != 0) ? 2 : 3));

            // one, few, many, other
            case 'mt':
                $n = $dc->getPluralOperand('n');

                $n100 = fmod($n, 100);

                return ($n == 1) ? 0 : (($n == 0 || ($n100 >= 2 && $n100 <= 10)) ? 1 : (($n100 >= 11 && $n100 <= 19) ? 2 : 3));

            // one, few, many, other
            case 'ru':
            case 'uk':
                $i = $dc->getPluralOperand('i');
                $v = $dc->getPluralOperand('v');

                $i10 = $i % 10;
                $i100 = $i % 100;

                return ($v == 0 && $i10 == 1 && $i100 != 11) ? 0 : (($v == 0 && $i10 >= 2 && $i10 <= 4 && ! ($i100 >= 12 && $i100 <= 14)) ? 1 : ((($v == 0 && $i10 == 0) || ($v == 0 && $i10 >= 5 && $i10 <= 9) || ($v == 0 && $i100 >= 11 && $i100 <= 14)) ? 2 : 3));

            // one, two, few, many, other
            case 'br':
                $n = $dc->getPluralOperand('n');

                $n10 = fmod($n, 10);
                $n100 = fmod($n, 100);

                return ($n10 == 1 && ! in_array($n100, [11, 71, 91])) ? 0 : (($n10 == 2 && ! in_array($n100, [12, 72, 92])) ? 1 : (((($n10 >= 3 && $n10 <= 4) || $n10 == 9) && ! (($n100 >= 10 && $n100 <= 19) || ($n100 >= 70 && $n100 <= 79) || ($n100 >= 90 && $n100 <= 99))) ? 2 : (($n != 0 && fmod($n, 1000000) == 0) ? 3 : 4)));

            // one, two, few, many, other
            case 'ga':
                $n = $dc->getPluralOperand('n');

                return ($n == 1) ? 0 : (($n == 2) ? 1 : (($n >= 3 && $n <= 6) ? 2 : (($n >= 7 && $n <= 10) ? 3 : 4)));

            // one, two, few, many, other
            case 'gv':
                $i = $dc->getPluralOperand('i');
                $v = $dc->getPluralOperand('v');

                $i10 = $i % 10;

                return ($v == 0 && $i10 == 1) ? 0 : (($v == 0 && $i10 == 2) ? 1 : (($v == 0 && in_array($i % 100, [0, 20, 40, 60, 80])) ? 2 : (($v != 0) ? 3 : 4)));

            // zero, one, two, few, many, other
            case 'ar':
            case 'ars':
                $n = $dc->getPluralOperand('n');

                $n100 = fmod($n, 100);

                return ($n == 0) ? 0 : (($n == 1) ? 1 : (($n == 2) ? 2 : (($n100 >= 3 && $n100 <= 10) ? 3 : (($n100 >= 11 && $n100 <= 99) ? 4 : 5))));

            // zero, one, two, few, many, other
            case 'cy':
                $n = $dc->getPluralOperand('n');

                return ($n == 0) ? 0 : (($n == 1) ? 1 : (($n == 2) ? 2 : (($n == 3) ? 3 : (($n == 6) ? 4 : 5))));

            // zero, one, two, few, many, other
            case 'kw':
                $n = $dc->getPluralOperand('n');

                $n100 = fmod($n, 100);
                $n100000 = fmod($n, 100000);

                return ($n == 0) ? 0 : (($n == 1) ? 1 : ((in_array($n100, [2, 22, 42, 62, 82]) || (fmod($n, 1000) == 0 && (($n100000 >= 1000 && $n100000 <= 20000) || in_array($n100000, [40000, 60000, 80000]))) || ($n != 0 && fmod($n, 1000000) == 100000)) ? 2 : (in_array($n100, [3, 23, 43, 63, 83]) ? 3 : (($n != 1 && in_array($n100, [1, 21, 41,61, 81])) ? 4 : 5))));

            default:
                return 0;
        }
    }
}
