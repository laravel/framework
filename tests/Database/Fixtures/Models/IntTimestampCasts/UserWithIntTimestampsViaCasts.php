<?php

namespace Illuminate\Tests\Database\Fixtures\Models\IntTimestampCasts;

use Illuminate\Database\Eloquent\Model;

class UserWithIntTimestampsViaCasts extends Model
{
    protected $table = 'users';

    protected $fillable = ['email'];

    protected $casts = [
        'created_at' => UnixTimeStampToCarbon::class,
        'updated_at' => UnixTimeStampToCarbon::class,
    ];
}
