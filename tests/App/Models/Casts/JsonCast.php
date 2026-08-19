<?php

namespace Illuminate\Tests\App\Models\Casts;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $basic_string_as_json_field
 * @property $json_string_as_json_field
 * @property $array_as_json_field
 * @property $object_as_json_field
 * @property $collection_as_json_field
 */
class JsonCast extends Model
{
    public $table = 'json_casts';
    public $timestamps = false;
    protected $guarded = [];

    public $casts = [
        'basic_string_as_json_field' => 'json',
        'json_string_as_json_field' => 'json',
        'array_as_json_field' => 'array',
        'object_as_json_field' => 'object',
        'collection_as_json_field' => 'collection',
    ];
}
