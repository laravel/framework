<?php

namespace Illuminate\Tests\Database\Fixtures\SomePath\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Database\Fixtures\Factories\SomePath\Models\SomeModelFactory;

class SomeModel extends Model
{
    use HasFactory;

    protected $table = 'table_name';

    public static function factory()
    {
        return SomeModelFactory::new();
    }
}
