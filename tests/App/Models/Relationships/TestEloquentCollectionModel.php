<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class TestEloquentCollectionModel extends Model
{
    protected $visible = ['visible'];
    protected $hidden = ['hidden'];

    public function getTestAttribute()
    {
        return 'test';
    }
}
