<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class HasManyThroughDefaultTestCountry extends Model
{
    protected $table = 'countries_default';
    protected $guarded = [];

    public function posts()
    {
        return $this->hasManyThrough(HasManyThroughDefaultTestPost::class, HasManyThroughDefaultTestUser::class);
    }

    public function users()
    {
        return $this->hasMany(HasManyThroughDefaultTestUser::class);
    }
}
