<?php

namespace Illuminate\Tests\App\Models\Keys;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ModelWithUlidPrimaryKey extends Model
{
    use HasUlids;

    protected $table = 'posts';

    protected $guarded = [];

    public function uniqueIds()
    {
        return [$this->getKeyName(), 'foo', 'bar'];
    }
}
