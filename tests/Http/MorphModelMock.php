<?php

namespace Illuminate\Tests\Http;

use Illuminate\Database\Eloquent\Model;

class MorphModelMock
{
    public static function find($id)
    {
        return tap(new class extends Model {}, fn($model) => $model->id = $id);
    }
}
