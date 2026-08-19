<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class HasManyThroughIntermediateTestCountry extends Model
{
    protected $table = 'countries';
    protected $guarded = [];

    public function posts()
    {
        return $this->hasManyThrough(HasManyThroughTestPost::class, HasManyThroughTestUser::class, 'country_short', 'email', 'shortname', 'email');
    }

    public function users()
    {
        return $this->hasMany(HasManyThroughTestUser::class, 'country_id');
    }
}
