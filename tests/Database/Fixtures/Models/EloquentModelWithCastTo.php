<?php

namespace Illuminate\Tests\Database\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed $castable
 */
class EloquentModelWithCastTo extends Model
{
    public static $useCasts = null;

    protected $table = 'users';

    protected $fillable = ['castable'];

    protected function casts(): array
    {
        return static::$useCasts;
    }
}
