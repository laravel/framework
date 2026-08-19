<?php

namespace Illuminate\Tests\App\Models\Keys;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ModelWithUuidPrimaryKey extends Model
{
    use HasUuids;

    protected $table = 'users';

    protected $guarded = [];

    public function uniqueIds()
    {
        return [$this->getKeyName(), 'foo', 'bar'];
    }
}
