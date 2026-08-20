<?php

namespace Illuminate\Tests\App\Models\Serialization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Integration\Queue\ModelSerializationTestCustomUserCollection;

class CustomCollectionUser extends Model
{
    public $table = 'users';
    public $guarded = [];
    public $timestamps = false;

    public function newCollection(array $models = [])
    {
        return new ModelSerializationTestCustomUserCollection($models);
    }
}
