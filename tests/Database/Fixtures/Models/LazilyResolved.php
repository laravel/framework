<?php

namespace Illuminate\Tests\Database\Fixtures\Models;

use Illuminate\Database\Eloquent\Concerns\DeferRouteBinding;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LazilyResolved extends Model
{
    use DeferRouteBinding;
    use SoftDeletes;

    protected $table = 'users';

    protected $fillable = ['name', 'email'];

    public static function factory()
    {
        return Factory::factoryForModel(User::class);
    }
}
