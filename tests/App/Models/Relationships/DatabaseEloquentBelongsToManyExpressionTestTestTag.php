<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class DatabaseEloquentBelongsToManyExpressionTestTestTag extends Model
{
    protected $table = 'tags';
    protected $fillable = ['id'];
    public $timestamps = false;
}
