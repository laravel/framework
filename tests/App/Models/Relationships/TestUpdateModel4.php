<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestUpdateModel4 extends Model
{
    use SoftDeletes;

    public $table = 'test_model4';
    protected $fillable = ['views', 'likes', 'name'];
    protected $casts = ['deleted_at' => 'datetime'];
}
