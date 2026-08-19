<?php

namespace Illuminate\Tests\App\Models\Keys;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ModelWithCustomUuidPrimaryKeyName extends Model
{
    use HasUuids;

    protected $table = 'pictures';

    protected $guarded = [];

    protected $primaryKey = 'uuid';
}
