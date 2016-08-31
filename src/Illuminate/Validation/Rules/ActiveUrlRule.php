<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class ActiveUrlRule extends Rule
{
    public function passes($attribute, $value, $parameters, $validator)
    {
        if (! is_string($value)) {
            return false;
        }

        if ($url = parse_url($value, PHP_URL_HOST)) {
            return count(dns_get_record($url, DNS_A | DNS_AAAA)) > 0;
        }

        return false;

    }
}
