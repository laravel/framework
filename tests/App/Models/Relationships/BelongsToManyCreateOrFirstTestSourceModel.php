<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BelongsToManyCreateOrFirstTestSourceModel extends Model
{
    protected $table = 'source_table';
    protected $guarded = [];

    public function related(): BelongsToMany
    {
        return $this->belongsToMany(
            BelongsToManyCreateOrFirstTestRelatedModel::class,
            'pivot_table',
            'source_id',
            'related_id',
        );
    }
}
