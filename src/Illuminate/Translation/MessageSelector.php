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
     * @param  string  $locale
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

        if (count($segments) === 1 || ! isset($segments[$pluralIndex])) {
            return $segments[0];
        }

        return $segments[$pluralIndex];
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

        if (count($matches) !== 3) {
            return null;
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
     * The plural rules are derived from code of the Zend Framework (2010-09-25), which
     * is subject to the new BSD license (https://framework.zend.com/license)
     * Copyright (c) 2005-2010 - Zend Technologies USA Inc. (http://www.zend.com)
     *
     * @param  string  $locale
     * @param  int  $number
     * @return int
     */
    public function getPluralIndex($locale, $number)
    {
        return match ($locale) {
            'az', 'az_AZ', 'bo', 'bo_CN', 'bo_IN', 'dz', 'dz_BT', 'id', 'id_ID', 'ja', 'ja_JP', 'jv', 'ka', 'ka_GE', 'km', 'km_KH', 'kn', 'kn_IN', 'ko', 'ko_KR', 'ms', 'ms_MY', 'th', 'th_TH', 'tr', 'tr_CY', 'tr_TR', 'vi', 'vi_VN', 'zh', 'zh_CN', 'zh_HK', 'zh_SG', 'zh_TW' => 0,
            'af', 'af_ZA', 'bn', 'bn_BD', 'bn_IN', 'bg', 'bg_BG', 'ca', 'ca_AD', 'ca_ES', 'ca_FR', 'ca_IT', 'da', 'da_DK', 'de', 'de_AT', 'de_BE', 'de_CH', 'de_DE', 'de_LI', 'de_LU', 'el', 'el_CY', 'el_GR', 'en', 'en_AG', 'en_AU', 'en_BW', 'en_CA', 'en_DK', 'en_GB', 'en_HK', 'en_IE', 'en_IN', 'en_NG', 'en_NZ', 'en_PH', 'en_SG', 'en_US', 'en_ZA', 'en_ZM', 'en_ZW', 'eo', 'eo_US', 'es', 'es_AR', 'es_BO', 'es_CL', 'es_CO', 'es_CR', 'es_CU', 'es_DO', 'es_EC', 'es_ES', 'es_GT', 'es_HN', 'es_MX', 'es_NI', 'es_PA', 'es_PE', 'es_PR', 'es_PY', 'es_SV', 'es_US', 'es_UY', 'es_VE', 'et', 'et_EE', 'eu', 'eu_ES', 'eu_FR', 'fa', 'fa_IR', 'fi', 'fi_FI', 'fo', 'fo_FO', 'fur', 'fur_IT', 'fy', 'fy_DE', 'fy_NL', 'gl', 'gl_ES', 'gu', 'gu_IN', 'ha', 'ha_NG', 'he', 'he_IL', 'hu', 'hu_HU', 'is', 'is_IS', 'it', 'it_CH', 'it_IT', 'ku', 'ku_TR', 'lb', 'lb_LU', 'ml', 'ml_IN', 'mn', 'mn_MN', 'mr', 'mr_IN', 'nah', 'nb', 'nb_NO', 'ne', 'ne_NP', 'nl', 'nl_AW', 'nl_BE', 'nl_NL', 'nn', 'nn_NO', 'no', 'om', 'om_ET', 'om_KE', 'or', 'or_IN', 'pa', 'pa_IN', 'pa_PK', 'pap', 'pap_AN', 'pap_AW', 'pap_CW', 'ps', 'ps_AF', 'pt', 'pt_BR', 'pt_PT', 'so', 'so_DJ', 'so_ET', 'so_KE', 'so_SO', 'sq', 'sq_AL', 'sq_MK', 'sv', 'sv_FI', 'sv_SE', 'sw', 'sw_KE', 'sw_TZ', 'ta', 'ta_IN', 'ta_LK', 'te', 'te_IN', 'tk', 'tk_TM', 'ur', 'ur_IN', 'ur_PK', 'zu', 'zu_ZA' => ($number == 1) ? 0 : 1,
            'am', 'am_ET', 'bh', 'fil', 'fil_PH', 'fr', 'fr_BE', 'fr_CA', 'fr_CH', 'fr_FR', 'fr_LU', 'gun', 'hi', 'hi_IN', 'hy', 'hy_AM', 'ln', 'ln_CD', 'mg', 'mg_MG', 'nso', 'nso_ZA', 'ti', 'ti_ER', 'ti_ET', 'wa', 'wa_BE', 'xbr' => (($number == 0) || ($number == 1)) ? 0 : 1,
            'be', 'be_BY', 'bs', 'bs_BA', 'hr', 'hr_HR', 'ru', 'ru_RU', 'ru_UA', 'sr', 'sr_ME', 'sr_RS', 'uk', 'uk_UA' => (($number % 10 == 1) && ($number % 100 != 11)) ? 0 : ((($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 10) || ($number % 100 >= 20))) ? 1 : 2),
            'cs', 'cs_CZ', 'sk', 'sk_SK' => ($number == 1) ? 0 : ((($number >= 2) && ($number <= 4)) ? 1 : 2),
            'ga', 'ga_IE' => ($number == 1) ? 0 : (($number == 2) ? 1 : 2),
            'lt', 'lt_LT' => (($number % 10 == 1) && ($number % 100 != 11)) ? 0 : ((($number % 10 >= 2) && (($number % 100 < 10) || ($number % 100 >= 20))) ? 1 : 2),
            'sl', 'sl_SI' => ($number % 100 == 1) ? 0 : (($number % 100 == 2) ? 1 : ((($number % 100 == 3) || ($number % 100 == 4)) ? 2 : 3)),
            'mk', 'mk_MK' => ($number % 10 == 1) ? 0 : 1,
            'mt', 'mt_MT' => ($number == 1) ? 0 : ((($number == 0) || (($number % 100 > 1) && ($number % 100 < 11))) ? 1 : ((($number % 100 > 10) && ($number % 100 < 20)) ? 2 : 3)),
            'lv', 'lv_LV' => ($number == 0) ? 0 : ((($number % 10 == 1) && ($number % 100 != 11)) ? 1 : 2),
            'pl', 'pl_PL' => ($number == 1) ? 0 : ((($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 12) || ($number % 100 > 14))) ? 1 : 2),
            'cy', 'cy_GB' => ($number == 1) ? 0 : (($number == 2) ? 1 : ((($number == 8) || ($number == 11)) ? 2 : 3)),
            'ro', 'ro_RO' => ($number == 1) ? 0 : ((($number == 0) || (($number % 100 > 0) && ($number % 100 < 20))) ? 1 : 2),
            'ar', 'ar_AE', 'ar_BH', 'ar_DZ', 'ar_EG', 'ar_IN', 'ar_IQ', 'ar_JO', 'ar_KW', 'ar_LB', 'ar_LY', 'ar_MA', 'ar_OM', 'ar_QA', 'ar_SA', 'ar_SD', 'ar_SS', 'ar_SY', 'ar_TN', 'ar_YE' => ($number == 0) ? 0 : (($number == 1) ? 1 : (($number == 2) ? 2 : ((($number % 100 >= 3) && ($number % 100 <= 10)) ? 3 : ((($number % 100 >= 11) && ($number % 100 <= 99)) ? 4 : 5)))),
            default => 0,
        };
    }
}
