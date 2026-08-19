<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class IrregularPluralMotto extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function irregularPluralHumans()
    {
        return $this->morphedByMany(IrregularPluralHuman::class, 'cool_motto');
    }
}
