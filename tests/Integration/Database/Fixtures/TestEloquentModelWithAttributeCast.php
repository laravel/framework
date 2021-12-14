<?php

namespace Illuminate\Tests\Integration\Database\Fixtures;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Tests\Integration\Database\AttributeCastAddress;

class TestEloquentModelWithAttributeCast extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]
     */
    protected $guarded = [];

    public function uppercase(): Attribute
    {
        return new Attribute(
            get: fn ($value) => strtoupper($value),
            set: fn ($value) => strtoupper($value),
        );
    }

    public function address(): Attribute
    {
        return new Attribute(
            get: function ($value, $attributes) {
                if (is_null($attributes['address_line_one'])) {
                    return;
                }

                return new AttributeCastAddress($attributes['address_line_one'], $attributes['address_line_two']);
            },
            set: function ($value) {
                if (is_null($value)) {
                    return [
                        'address_line_one' => null,
                        'address_line_two' => null,
                    ];
                }

                return ['address_line_one' => $value->lineOne, 'address_line_two' => $value->lineTwo];
            },
        );
    }

    public function options(): Attribute
    {
        return new Attribute(
            get: fn ($value) => json_decode($value, true),
            set: fn ($value) => json_encode($value),
        );
    }

    public function birthdayAt(): Attribute
    {
        return new Attribute(
            get: fn ($value) => Carbon::parse($value),
            set: fn ($value) => $value->format('Y-m-d'),
        );
    }

    public function password(): Attribute
    {
        return new Attribute(
            set: fn ($value) => hash('sha256', $value)
        );
    }
}
