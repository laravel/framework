<?php

namespace Illuminate\Tests\App\Models\Locale;

use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Model;

class TestEmailLocaleUser extends Model implements HasLocalePreference
{
    protected $fillable = [
        'email',
        'email_locale',
    ];

    public function preferredLocale()
    {
        return $this->email_locale;
    }
}
