<?php

namespace Illuminate\Tests\App\Models\Keys;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ModelUpsertWithUuidPrimaryKey extends Model
{
    use HasUuids;

    protected $table = 'foo';

    protected $guarded = [];

    public function uniqueIds()
    {
        return [$this->getKeyName()];
    }
}
