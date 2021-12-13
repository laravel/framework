<?php

namespace Illuminate\Tests\Integration\Database\Fixtures;

use Illuminate\Database\Eloquent\Casts\AsAttribute;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TestEloquentModelWithAttributeCast extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]
     */
    protected $guarded = [];

    #[AsAttribute]
    public function uppercase()
    {
        return new Attribute(
            get: fn ($value) => strtoupper($value),
            set: fn ($value) => strtoupper($value),
        );
    }

    #[AsAttribute]
    public function address()
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

    #[AsAttribute]
    public function options()
    {
        return new Attribute(
            get: fn ($value) => json_decode($value, true),
            set: fn ($value) => json_encode($value),
        );
    }

    #[AsAttribute]
    public function birthdayAt()
    {
        return new Attribute(
            get: fn ($value) => Carbon::parse($value),
            set: fn ($value) => $value->format('Y-m-d'),
        );
    }

    #[AsAttribute]
    public function password()
    {
        return new Attribute(
            set: fn ($value) => hash('sha256', $value)
        );
    }
}

class AttributeCastAddress
{
    public $lineOne;
    public $lineTwo;

    public function __construct($lineOne, $lineTwo)
    {
        $this->lineOne = $lineOne;
        $this->lineTwo = $lineTwo;
    }
}
