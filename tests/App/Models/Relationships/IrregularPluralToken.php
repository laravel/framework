<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class IrregularPluralToken extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    protected $touches = [
        'irregularPluralHumans',
    ];
}
