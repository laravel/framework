<?php

namespace Illuminate\Tests\App\Models\Keys;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ModelWithoutUuidPrimaryKey extends Model
{
    use HasUuids;

    protected $table = 'songs';

    protected $guarded = [];

    public function uniqueIds()
    {
        return ['foo', 'bar'];
    }
}
