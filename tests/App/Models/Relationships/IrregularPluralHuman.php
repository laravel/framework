<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class IrregularPluralHuman extends Model
{
    protected $guarded = [];

    public function irregularPluralTokens()
    {
        return $this->belongsToMany(
            IrregularPluralToken::class,
            'irregular_plural_human_irregular_plural_token',
            'irregular_plural_token_id',
            'irregular_plural_human_id'
        );
    }

    public function mottoes()
    {
        return $this->morphToMany(IrregularPluralMotto::class, 'cool_motto');
    }
}
