<?php

namespace Illuminate\Tests\App\Models\Casts;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Eloquent Models...
 */
class StringCasts extends Eloquent
{
    /**
     * @var string
     */
    protected $table = 'casting_table';

    /**
     * @var string[]
     */
    protected $guarded = [];

    /**
     * @var array
     */
    protected $casts = [
        'array_attributes' => 'array',
        'json_attributes' => 'json',
        'object_attributes' => 'object',
    ];
}
