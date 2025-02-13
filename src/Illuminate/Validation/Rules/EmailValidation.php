<?php

namespace Illuminate\Validation\Rules;

use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\EmailValidation as EguliasEmailValidation;
use Egulias\EmailValidator\Validation\Extra\SpoofCheckValidation;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use Egulias\EmailValidator\Validation\RFCValidation;
use Illuminate\Validation\Concerns\FilterEmailValidation;

enum EmailValidation
{
    case Strict;
    case Dns;
    case Spoof;
    case Filter;
    case Rfc;

    public function validation(): EguliasEmailValidation
    {
        return match ($this) {
            self::Strict => new NoRFCWarningsValidation(),
            self::Dns => new DNSCheckValidation(),
            self::Spoof => new SpoofCheckValidation(),
            self::Filter => new FilterEmailValidation(),
            self::Rfc => new RFCValidation(),
            default => new RFCValidation(),
        };
    }
}
