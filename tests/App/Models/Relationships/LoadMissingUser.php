<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class LoadMissingUser extends Model
{
    protected $table = 'users';

    public $timestamps = false;
    protected $guarded = [];

    public function posts()
    {
        return $this->hasMany(LoadMissingPost::class, 'user_id');
    }
}
