<?php

namespace Illuminate\Tests\App\Models\Casts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\App\Casts\UnixTimeStampToCarbon;

class UserWithIntTimestampsViaCasts extends Model
{
    protected $table = 'users';

    protected $fillable = ['email'];

    protected $casts = [
        'created_at' => UnixTimeStampToCarbon::class,
        'updated_at' => UnixTimeStampToCarbon::class,
    ];
}
