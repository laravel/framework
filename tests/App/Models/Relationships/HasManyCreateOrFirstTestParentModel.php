<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HasManyCreateOrFirstTestParentModel extends Model
{
    protected $table = 'parent_table';
    protected $guarded = [];

    public function children(): HasMany
    {
        return $this->hasMany(HasManyCreateOrFirstTestChildModel::class, 'parent_id');
    }
}
