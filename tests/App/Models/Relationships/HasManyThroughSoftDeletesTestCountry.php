<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class HasManyThroughSoftDeletesTestCountry extends Model
{
    protected $table = 'countries';
    protected $guarded = [];

    public function posts()
    {
        return $this->hasManyThrough(HasManyThroughSoftDeletesTestPost::class, HasManyThroughTestUser::class, 'country_id', 'user_id');
    }

    public function users()
    {
        return $this->hasMany(HasManyThroughSoftDeletesTestUser::class, 'country_id');
    }
}
