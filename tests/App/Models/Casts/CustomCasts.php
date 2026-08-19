<?php

namespace Illuminate\Tests\App\Models\Casts;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Tests\App\Casts\AddressCast;
use Illuminate\Tests\App\Casts\GMPCast;
use Illuminate\Tests\App\Casts\NonNullableString;

class CustomCasts extends Eloquent
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
        'address' => AddressCast::class,
        'amount' => GMPCast::class,
        'string_field' => NonNullableString::class,
    ];
}
