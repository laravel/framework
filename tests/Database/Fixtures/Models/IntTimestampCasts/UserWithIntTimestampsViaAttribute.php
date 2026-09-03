<?php

namespace Illuminate\Tests\Database\Fixtures\Models\IntTimestampCasts;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class UserWithIntTimestampsViaAttribute extends Model
{
    protected $table = 'users';

    protected $fillable = ['email'];

    protected function updatedAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value),
            set: fn ($value) => Carbon::parse($value)->getTimestamp(),
        );
    }

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value),
            set: fn ($value) => Carbon::parse($value)->getTimestamp(),
        );
    }
}
